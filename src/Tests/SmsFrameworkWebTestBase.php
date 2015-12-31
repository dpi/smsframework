<?php

/**
 * @file
 * Contains \Drupal\sms\Tests\SmsFrameworkWebTestBase.
 */

namespace Drupal\sms\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\sms\Entity\SmsGateway;
use Drupal\Component\Utility\Unicode;

/**
 * Provides commonly used functionality for tests.
 */
abstract class SmsFrameworkWebTestBase extends WebTestBase {

  public static $modules = ['sms', 'sms_test_gateway'];

  /**
   * The gateway manager.
   *
   * @var \Drupal\sms\Gateway\GatewayManagerInterface
   */
  protected $gatewayManager;

  /**
   * Test gateway.
   *
   * @var \Drupal\sms\SmsGatewayInterface
   */
  protected $test_gateway;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->gatewayManager = $this->container->get('plugin.manager.sms_gateway');
    // Add an instance of test gateway.
    $this->test_gateway = SmsGateway::create([
      'plugin' => 'memory',
      'id' => Unicode::strtolower($this->randomMachineName(16)),
      'label' => $this->randomString(),
    ]);
    $this->test_gateway->enable();
    $this->test_gateway->save();
  }

  /**
   * Get the last SMS message sent to 'Memory' gateway.
   *
   * @return \Drupal\sms\Message\SmsMessageInterface|NULL
   *   The last SMS message, or FALSE if no messages have been sent.
   */
  public function getLastTestMessage() {
    $sms_messages = \Drupal::state()->get('sms_test_gateway.memory.send', []);
    return end($sms_messages);
  }

  /**
   * Resets SMS messages stored in memory by 'Memory' gateway.
   */
  public function resetTestMessages() {
    \Drupal::state()->set('sms_test_gateway.memory.send', []);
  }

}
