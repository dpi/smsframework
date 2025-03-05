<?php

declare(strict_types=1);

namespace Drupal\Tests\sms\Kernel;

use Drupal\Core\CronInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\sms\Entity\PhoneNumberVerification;
use Drupal\sms\PhoneNumber\PhoneNumber;
use Drupal\sms\PhoneNumberVerification\CodeGenerator\VerificationCode;
use Drupal\sms\PhoneNumberVerification\Enum\Verified;
use Drupal\sms\PhoneNumberVerification\Exception\NoPhoneNumberVerificationByCodeExists;
use Drupal\sms\PhoneNumberVerification\SmsPhoneNumberVerificationInterface;
use Drupal\Tests\sms\Traits\SmsLoggerTrait;
use Drupal\user\Entity\User;

/**
 * Tests SMS phone number service.
 *
 * @coversDefaultClass \Drupal\sms\PhoneNumberVerification\SmsPhoneNumberVerification
 */
final class SmsPhoneNumberVerificationTest extends KernelTestBase {

  use SmsLoggerTrait;

  protected const FIELD_NAME = 'phone_number';

  protected static $modules = [
    'another_entity_iterator',
    'bca',
    'dynamic_entity_reference',
    'entity_test',
    'notifier',
    'sms',
    'system',
    'telephone',
    'user',
    'field',
  ];

  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema(PhoneNumberVerification::ENTITY_TYPE_ID);
    $this->installSchema('user', ['users_data']);
    $this->createPhoneNumberField('user', 'user', static::FIELD_NAME);
  }

  /**
   * @covers ::isVerified
   * @covers ::getPhoneNumberVerification
   * @covers ::verify
   * @covers ::newPhoneVerification
   */
  public function testIsVerified(): void {
    [$recipient, $phoneNumber] = $this->createEntity();

    // Ensure no verification was created by entity insert/save.
    static::assertNull(static::phoneNumberVerification()->getPhoneNumberVerification($recipient, $phoneNumber));
    static::assertEquals(Verified::Unverified, static::phoneNumberVerification()->isVerified($recipient, $phoneNumber));

    static::phoneNumberVerification()->newPhoneVerification($recipient, $phoneNumber);
    static::assertNotNull(static::phoneNumberVerification()->getPhoneNumberVerification($recipient, $phoneNumber));
    static::assertEquals(Verified::Unverified, static::phoneNumberVerification()->isVerified($recipient, $phoneNumber));

    static::phoneNumberVerification()->verify($recipient, $phoneNumber);
    static::assertEquals(Verified::Verified, static::phoneNumberVerification()->isVerified($recipient, $phoneNumber));
  }

  /**
   * @covers ::claimableVerificationByCodeExists
   */
  public function testClaimableVerificationByCodeExists(): void {
    [$recipient, $phoneNumber] = $this->createEntity();

    static::assertNull(static::phoneNumberVerification()->getPhoneNumberVerification($recipient, $phoneNumber));
    static::phoneNumberVerification()->newPhoneVerification($recipient, $phoneNumber);
    $verification = static::phoneNumberVerification()->getPhoneNumberVerification($recipient, $phoneNumber);
    static::assertNotNull($verification);

    static::assertFalse(static::phoneNumberVerification()->claimableVerificationByCodeExists(new VerificationCode('123')));
    $verificationCode = $verification->getVerificationCode();
    static::assertNotNull($verificationCode);
    static::assertTrue(static::phoneNumberVerification()->claimableVerificationByCodeExists($verificationCode));
  }

  /**
   * @covers ::verifyByCode
   */
  public function testVerifyByCode(): void {
    [$recipient, $phoneNumber] = $this->createEntity();

    static::assertNull(static::phoneNumberVerification()->getPhoneNumberVerification($recipient, $phoneNumber));
    static::phoneNumberVerification()->newPhoneVerification($recipient, $phoneNumber);
    $verification = static::phoneNumberVerification()->getPhoneNumberVerification($recipient, $phoneNumber);

    // No exceptions = pass.
    static::assertNotNull($verification);
    $verificationCode = $verification->getVerificationCode();
    static::assertNotNull($verificationCode);
    static::phoneNumberVerification()->verifyByCode($verificationCode);
  }

  /**
   * @covers ::verifyByCode
   */
  public function testVerifyByCodeException(): void {
    static::expectException(NoPhoneNumberVerificationByCodeExists::class);
    static::phoneNumberVerification()->verifyByCode(new VerificationCode('+123456789'));
  }

  /**
   * @covers ::purgeExpiredVerifications
   */
  public function testPurgeExpiredVerifications(): void {
    [$recipient, $phoneNumber] = $this->createEntity();
    static::phoneNumberVerification()->newPhoneVerification($recipient, $phoneNumber);
    self::assertEquals(1, $this->countVerifications());

    static::phoneNumberVerification()->purgeExpiredVerifications();
    static::runCron();
    self::assertEquals(1, $this->countVerifications());

    $verification = static::phoneNumberVerification()->getPhoneNumberVerification($recipient, $phoneNumber);
    static::assertNotNull($verification);

    $verification
      ->setCreatedDate(new \DateTimeImmutable('1 year ago'))
      ->setStatus(Verified::Verified)
      ->save();

    static::phoneNumberVerification()->purgeExpiredVerifications();
    static::runCron();
    self::assertEquals(1, $this->countVerifications());

    $verification->setStatus(Verified::Unverified)->save();
    static::phoneNumberVerification()->purgeExpiredVerifications();
    static::runCron();
    self::assertEquals(0, $this->countVerifications());
  }

  /**
   * Ensure phone number verification are deleted.
   *
   * @see \Drupal\sms\Hook\SmsVerificationHooks::entityDelete
   */
  public function testPhoneNumberVerificationDeleted(): void {
    $entities = [];
    for ($i = 0; $i < 3; $i++) {
      [$recipient, $phoneNumber] = $this->createEntity();
      static::phoneNumberVerification()->newPhoneVerification($recipient, $phoneNumber);
      $entities[] = $recipient;
    }

    static::assertEquals(3, $this->countVerificationCodes());
    $entities[1]->delete();
    static::runCron();
    static::assertEquals(2, $this->countVerificationCodes());
  }

  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container->setParameter('notifier.channel_policy', [
      'urgent' => ['sms'],
      'high' => ['sms'],
      'medium' => ['sms'],
      'low' => ['sms'],
    ]);
    $container->setParameter('notifier.field_mapping.sms', [
      [
        'entity_type' => 'user',
        'bundle' => 'user',
        'field_name' => static::FIELD_NAME,
        'verifications' => FALSE,
      ],
    ]);
    $container->setParameter('sms.transports', [
      'fakesms+logger' => 'fakesms+logger://default',
    ]);
    $this->loggerRegister($container);
  }

  protected function tearDown(): void {
    $this->loggerTeardown();
    parent::tearDown();
  }

  private static function phoneNumberVerification(): SmsPhoneNumberVerificationInterface {
    return \Drupal::service(SmsPhoneNumberVerificationInterface::class);
  }

  protected function createPhoneNumberField(string $entityTypeId, string $bundle, ?string $fieldName): FieldConfigInterface {
    $fieldName ??= \mb_strtolower($this->randomMachineName());
    $storage = FieldStorageConfig::create([
      'entity_type' => $entityTypeId,
      'field_name' => $fieldName,
      'type' => 'telephone',
    ]);
    $storage->save();

    $config = FieldConfig::create([
      'entity_type' => $entityTypeId,
      'bundle' => $bundle,
      'field_name' => $storage->getName(),
    ]);
    $config->save();

    return $config;
  }

  /**
   * @phpstan-return array{\Drupal\Core\Entity\FieldableEntityInterface, \Drupal\sms\PhoneNumber\PhoneNumberInterface}
   */
  private function createEntity(): array {
    $phoneNumber = PhoneNumber::create('+123');
    $recipient = User::create(['name' => $this->randomMachineName()]);
    $recipient->set(static::FIELD_NAME, $phoneNumber->getPhoneNumber());
    $recipient->save();

    return [$recipient, $phoneNumber];
  }

  private static function runCron(): void {
    \Drupal::service(CronInterface::class)->run();
  }

  /**
   * Count verification codes in database.
   *
   * @phpstan-return int<0, max>
   */
  private function countVerificationCodes(): int {
    $query = \Drupal::entityTypeManager()
      ->getStorage('sms_phone_number_verification')
      ->getQuery()
      ->accessCheck(FALSE);

    /** @var int<0, max> */
    return (int) $query->count()->execute();
  }

  /**
   * @phpstan-return int<0, max>
   */
  protected function countVerifications(): int {
    /** @var int<0, max> */
    return \Drupal::entityTypeManager()
      ->getStorage(PhoneNumberVerification::ENTITY_TYPE_ID)
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();
  }

}
