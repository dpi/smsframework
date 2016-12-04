<?php

namespace Drupal\Tests\sms\Kernel;

use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Direction;

/**
 * Tests SMS Framework gateway plugins.
 *
 * @group SMS Framework
 */
class SmsFrameworkGatewayPluginTest extends SmsFrameworkKernelBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'sms', 'sms_test', 'sms_test_gateway', 'field', 'telephone',
    'dynamic_entity_reference',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('sms');
    $this->smsProvider = $this->container->get('sms.provider');
  }

  /**
   * Tests if incoming event is fired on a gateway plugin.
   */
  public function testIncomingEvent() {
    $gateway = $this->createMemoryGateway()
      ->setSkipQueue(TRUE);
    $gateway->save();

    $sms_message = SmsMessage::create()
      ->setDirection(Direction::INCOMING)
      ->setMessage($this->randomString())
      ->addRecipients($this->randomPhoneNumbers())
      ->setGateway($gateway);
    $sms_message->setResult($this->createMessageResult($sms_message));

    $this->smsProvider->queue($sms_message);
    $this->assertTrue(\Drupal::state()->get('sms_test_gateway.memory.incoming'));
  }

}
