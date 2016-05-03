<?php

namespace Drupal\Tests\sms\Kernel;

use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Entity\SmsMessageInterface;

/**
 * Tests SMS Framework gateway plugins.
 *
 * @group SMS Framework
 */
class SmsFrameworkGatewayPluginTest extends SmsFrameworkKernelBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['sms', 'sms_test', 'sms_test_gateway', 'field', 'telephone', 'dynamic_entity_reference'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('sms');
    $this->smsProvider = $this->container->get('sms_provider');
  }

  /**
   * Ensure incoming hook is fired on a gateway plugin implementing
   * SmsGatewayPluginIncomingInterface.
   */
  public function testIncoming() {
    $gateway = $this->createMemoryGateway([
      'plugin' => 'memory',
    ])->setSkipQueue(TRUE);
    $gateway->save();
    $this->smsProvider->setDefaultGateway($gateway);

    $sms_message = SmsMessage::create()
      ->setDirection(SmsMessageInterface::DIRECTION_INCOMING)
      ->setMessage($this->randomString())
      ->addRecipients($this->randomPhoneNumbers());

    $this->smsProvider->queue($sms_message);
    $this->assertTrue(\Drupal::state()->get('sms_test_gateway.memory.incoming_hook_temporary'));
  }

  /**
   * Ensure SMS provider does not crash and burn if the gateway does not
   * implement SmsGatewayPluginIncomingInterface.
   */
  public function testIncomingMissing() {
    $gateway = $this->createMemoryGateway([
      'plugin' => 'memory_noincoming',
    ])->setSkipQueue(TRUE);
    $gateway->save();
    $this->smsProvider->setDefaultGateway($gateway);

    $sms_message = SmsMessage::create()
      ->setDirection(SmsMessageInterface::DIRECTION_INCOMING)
      ->setMessage($this->randomString())
      ->addRecipients($this->randomPhoneNumbers());

    $this->smsProvider->queue($sms_message);
    $this->assertTrue(\Drupal::state()->get('sms_test_sms_incoming_preprocess'));
  }

}
