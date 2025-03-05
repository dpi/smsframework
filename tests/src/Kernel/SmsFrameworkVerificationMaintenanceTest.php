<?php

declare(strict_types=1);

namespace Drupal\Tests\sms\Kernel;

use Drupal\Core\CronInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\sms\Entity\PhoneNumberVerification;
use Drupal\sms\PhoneNumberVerification\Enum\Verified;

/**
 * Tests verification maintenance executed during cron.
 *
 * @group SMS Framework
 *
 * @phpstan-import-type SmsVerificationParameter from \Drupal\sms\SmsCompilerPass
 */
final class SmsFrameworkVerificationMaintenanceTest extends SmsFrameworkKernelBase {

  use \Drupal\Tests\sms\Trait\SmsFrameworkTestTrait;

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
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema(PhoneNumberVerification::ENTITY_TYPE_ID);

    $this->createPhoneNumberField('entity_test', 'entity_test', static::FIELD_NAME);
  }

  /**
   * Test unverified verification which have not expired.
   */
  public function testVerificationUnverifiedNotExpired(): void {
    $this->testEntity();

    $verification = static::getLastVerificationOrException();
    $verification
      ->setStatus(Verified::Unverified)
      ->save();
    static::runCron();
    static::assertNotNull(static::getLastVerification());
  }

  /**
   * Test unverified verification which have expired are deleted.
   */
  public function testVerificationUnverifiedExpired(): void {
    $this->testEntity();
    $verification = static::getLastVerificationOrException();
    $verification
      ->setStatus(Verified::Unverified)
      ->setCreatedDate(new \DateTimeImmutable('1 year ago'))
      ->save();
    static::runCron();
    static::assertNull(static::getLastVerification());
  }

  /**
   * Test unverified verification which have expired do not purge field data.
   */
  public function testVerificationUnverifiedExpiredNoPurgeFieldData(): void {
    /** @var SmsVerificationParameter $param */
    $param = \Drupal::getContainer()->getParameter('sms.verification');
    $param['unverified']['purge_fields'] = FALSE;
    $this->setVerificationsParameter($param);

    $testEntity = $this->testEntity();

    $verification = static::getLastVerificationOrException();
    $verification
      ->setStatus(Verified::Unverified)
      ->setCreatedDate(new \DateTimeImmutable('1 year ago'))
      ->save();
    static::runCron();
    $testEntity = EntityTest::load($testEntity->id());
    static::assertNotNull($testEntity);
    static::assertEquals('+123123123', $testEntity->get(static::FIELD_NAME)->value);
  }

  /**
   * Test unverified verification which have expired purge field data.
   */
  public function testVerificationUnverifiedExpiredPurgeFieldData(): void {
    /** @var SmsVerificationParameter $param */
    $param = \Drupal::getContainer()->getParameter('sms.verification');
    $param['unverified']['purge_fields'] = TRUE;
    $this->setVerificationsParameter($param);

    $testEntity = $this->testEntity();

    $verification = static::getLastVerificationOrException();
    $verification
      ->setStatus(Verified::Unverified)
      ->setCreatedDate(new \DateTimeImmutable('1 year ago'))
      ->save();
    static::runCron();
    $testEntity = EntityTest::load($testEntity->id());
    static::assertNotNull($testEntity);
    static::assertNull($testEntity->get(static::FIELD_NAME)->value);
  }

  /**
   * Test verified verification.
   */
  public function testVerificationVerifiedExpired(): void {
    $this->testEntity();
    $verification = static::getLastVerificationOrException();
    $verification
      ->setStatus(Verified::Verified)
      ->setCreatedDate(new \DateTimeImmutable('1 year ago'))
      ->save();
    static::runCron();
    static::assertNotNull(static::getLastVerification());
  }

  private static function runCron(): void {
    \Drupal::service(CronInterface::class)->run();
  }

  private function testEntity(): EntityTest {
    $testEntity = EntityTest::create(['name' => $this->randomMachineName()]);
    $testEntity->set(static::FIELD_NAME, ['+123123123']);
    $testEntity->save();
    return $testEntity;
  }

}
