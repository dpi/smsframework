<?php

declare(strict_types=1);

namespace Drupal\Tests\sms\Kernel;

use Drupal\sms\Direction;
use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Provider\SmsProviderInterface;
use Drupal\sms_test_gateway\EventSubscriber\SmsTestGatewayEventSubscriber;

/**
 * Tests SMS Framework gateway plugins.
 *
 * @group SMS Framework
 */
final class SmsFrameworkGatewayPluginTest extends SmsFrameworkKernelBase {

  protected static $modules = [
    'sms',
    'sms_test',
    'sms_test_gateway',
    'field',
    'telephone',
    'dynamic_entity_reference',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('sms');
    $this->installEntitySchema('sms_result');
    $this->installEntitySchema('sms_report');
  }

  /**
   * Tests if incoming event is fired on a gateway plugin.
   */
  public function testIncomingEvent(): void {
    $gateway = $this->createMemoryGateway()
      ->setSkipQueue(TRUE);
    $gateway->save();

    $sms_message = SmsMessage::create()
      ->setDirection(Direction::INCOMING)
      ->setMessage($this->randomString())
      ->addRecipients($this->randomPhoneNumbers())
      ->setGateway($gateway);
    $sms_message->setResult($this->createMessageResult($sms_message));

    /** @var \Drupal\sms\Provider\SmsProviderInterface $smsProvider */
    $smsProvider = \Drupal::service(SmsProviderInterface::class);
    $smsProvider->queue($sms_message);

    /** @var array $state */
    $state = \Drupal::state()->get(SmsTestGatewayEventSubscriber::STATE_MEMORY_INCOMING);
    static::assertCount(1, $state);
  }

}
