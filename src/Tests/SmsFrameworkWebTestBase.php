<?php

/**
 * @file
 * Contains \Drupal\sms\Tests\SmsFrameworkWebTestBase.
 */

namespace Drupal\sms\Tests;

use \Drupal\simpletest\WebTestBase;

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
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->gatewayManager = $this->container->get('plugin.manager.sms_gateway');
    // Add an instance of test gateway.
    $this->gatewayManager->addGateway('test', ['name' => 'test']);
  }

  /**
   * Sets the specified gateway as the default.
   *
   * @param string $gateway_id
   *   The ID of the gateway to be set as default.
   */
  public function setDefaultGateway($gateway_id) {
    // Ensure gateway is enabled first.
    $this->gatewayManager->setEnabledGateways([$gateway_id]);
    $this->gatewayManager->setDefaultGateway($gateway_id);
  }

}
