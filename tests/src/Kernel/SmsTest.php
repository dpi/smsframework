<?php

declare(strict_types=1);

namespace Drupal\Tests\sms\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupal\sms_entity_test\Entity\SmsTestEntity;
use Drupal\Tests\sms\Traits\SmsLoggerTrait;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;

/**
 * Tests SMS.
 */
final class SmsTest extends KernelTestBase {

  use SmsLoggerTrait;

  protected static $modules = [
    'another_entity_iterator',
    'bca',
    'dynamic_entity_reference',
    'entity_test',
    'notifier',
    'sms',
    'sms_entity_test',
    'system',
    'user',
  ];

  /**
   * Tests sending a message to a recipient.
   */
  public function testIntegration(): void {
    $this->installEntitySchema('entity_test');

    // Test with bundle classes :).
    $recipient = SmsTestEntity::create();
    $recipient->save();

    $notification = (new Notification())
      ->subject('Test message');

    static::notifier()->send($notification, $recipient);

    $logs = $this->getLogs();
    static::assertCount(1, $logs);
    static::assertEquals('New SMS on phone number: +1123123123123', $logs[0]['message']);
  }

  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container->setParameter('notifier.channel_policy', [
      'urgent' => ['sms'],
      'high' => ['sms'],
      'medium' => ['sms'],
      'low' => ['sms'],
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

  private static function notifier(): NotifierInterface {
    return \Drupal::service(NotifierInterface::class);
  }

}
