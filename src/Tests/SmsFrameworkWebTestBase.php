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

  public static $modules = ['sms', 'sms_test_gateway', 'telephone', 'dynamic_entity_reference'];

  /**
   * The gateway manager.
   *
   * @var \Drupal\sms\Plugin\SmsGatewayPluginManagerInterface
   */
  protected $gatewayManager;

  /**
   * The default SMS provider service.
   *
   * @var \Drupal\sms\Provider\DefaultSmsProvider
   */
  protected $defaultSmsProvider;

  /**
   * 'Memory' test gateway instance.
   *
   * @var \Drupal\sms\Entity\SmsGatewayInterface
   */
  protected $testGateway;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->gatewayManager = $this->container->get('plugin.manager.sms_gateway');
    $this->defaultSmsProvider = $this->container->get('sms_provider.default');

    // Add an instance of test gateway.
    $this->testGateway = SmsGateway::create([
      'plugin' => 'memory',
      'id' => Unicode::strtolower($this->randomMachineName(16)),
      'label' => $this->randomString(),
    ]);
    $this->testGateway->enable();
    $this->testGateway->save();
  }

  /**
   * Get all SMS messages sent to 'Memory' gateway.
   *
   * @return \Drupal\sms\Message\SmsMessageInterface[]
   */
  function getTestMessages() {
    return \Drupal::state()->get('sms_test_gateway.memory.send', []);
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
