<?php

declare(strict_types=1);

namespace Drupal\Tests\sms\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\sms\Entity\PhoneNumberVerification;
use Drupal\sms\PhoneNumber\QueryOptions;
use Drupal\sms\PhoneNumber\SmsPhoneNumberInterface;
use Drupal\sms\PhoneNumberVerification\Enum\VerificationRequirement;
use Drupal\Tests\sms\Traits\SmsLoggerTrait;
use Drupal\user\Entity\User;
use Symfony\Component\Notifier\Notification\Notification;

/**
 * Tests SMS phone number service.
 *
 * @coversDefaultClass \Drupal\sms\PhoneNumber\SmsPhoneNumber
 */
final class SmsPhoneNumberTest extends KernelTestBase {

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

  /**
   * Tests sending a message to a recipient via SMS services.
   *
   * @covers ::send
   * @covers ::getPhoneNumbers
   */
  public function testSmsPhoneNumber(): void {
    $this->installEntitySchema('user');
    $this->installEntitySchema(PhoneNumberVerification::ENTITY_TYPE_ID);
    $this->createPhoneNumberField('user', 'user', static::FIELD_NAME);

    $phoneNumber = $this->randomMachineName();
    $recipient = User::create(['name' => $this->randomMachineName()]);
    $recipient->set(static::FIELD_NAME, $phoneNumber);
    $recipient->save();

    // Expect nothing since the phone numbers are not verified.
    $phoneNumbers = static::phoneNumber()->getPhoneNumbers($recipient);
    static::assertCount(0, \iterator_to_array($phoneNumbers));

    $phoneNumbers = static::phoneNumber()->getPhoneNumbers($recipient, options: new QueryOptions(verified: VerificationRequirement::Any));
    static::assertEquals($phoneNumber, \iterator_to_array($phoneNumbers)[0]->getPhoneNumber());

    $notification = (new Notification())
      ->subject('Test message');

    static::phoneNumber()->send($recipient, $notification, options: new QueryOptions(verified: VerificationRequirement::Any));

    $logs = $this->getLogs();
    static::assertCount(1, $logs);
    static::assertEquals(\sprintf('New SMS on phone number: %s', $phoneNumber), $logs[0]['message']);
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

  private static function phoneNumber(): SmsPhoneNumberInterface {
    return \Drupal::service(SmsPhoneNumberInterface::class);
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

}
