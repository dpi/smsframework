<?php

/**
 * @file
 * Contains \Drupal\sms\Tests\SmsFrameworkWebTest.
 */

namespace Drupal\sms\Tests;

/**
 * Integration tests for the SMS Framework.
 *
 * @group SMS Framework
 */
class SmsFrameworkWebTest extends SmsFrameworkWebTestBase {

  /**
   * Tests the HookGateway implementation.
   */
  public function testHookGatewayIntegration() {
    // Test that hook gateway plugins are correctly discovered.
    $gateway_plugins = $this->gatewayManager->getGatewayPlugins();
    $this->assertEqual(array_keys($gateway_plugins), ['log', 'test'], 'Hook-based gateway discovered.');
    $this->assertEqual($gateway_plugins['test']['hook_info'], sms_test_gateway_gateway_info()['test'], 'sms_test_gateway hooks correct.');

    // Confirm the existence of the test gateway.
    $test_gateway = $this->gatewayManager->getGateway('test');
    $this->assertNotNull($test_gateway, 'Test gateway not null');

    // Add an instance and confirm that it exists
    $this->gatewayManager->addGateway('test', ['name' => 'test_instance', 'label' => 'Test gateway instance']);
    $test_gateway = $this->gatewayManager->getGateway('test_instance');
    $this->assertEqual(get_class($test_gateway), 'Drupal\sms\Gateway\HookGateway');
    $this->assertEqual($test_gateway->getLabel(), 'Test gateway instance');
  }

  /**
   * Tests that the correct gateways list is obtained.
   */
  public function testGatewaysList() {
    $test_gateways = [
      'log' => 'Log only',
      'test' => 'For testing',
    ];
    $this->assertEqual($test_gateways, sms_gateways('names'));
  }

  /**
   * Tests the add gateway functionality.
   */
  public function testAddGateways() {
    $gateways = ['log', 'test'];
    $this->assertEqual($gateways, array_keys($this->gatewayManager->getAvailableGateways()));
    for ($i = 0; $i < 3; $i++) {
      $name = $this->randomMachineName();
      $this->gatewayManager->addGateway('test', ['name' => $name]);
      // GatewayManagerInterface::getAvailableGateways() sorts by the names
      // before adding, so we need to simulate in the expected result.
      sort($gateways);
      $gateways[] = $name;
      $this->assertEqual($gateways, array_keys($this->gatewayManager->getAvailableGateways()));
    }
  }

  /**
   * Tests setting up the default gateway.
   */
  public function testDefaultGateway() {
    // Test initial default gateway.
    $gw = sms_default_gateway();
    $this->assertEqual($gw->getIdentifier(), 'log', 'Initial default gateway is "log".');

    $this->drupalLogin($this->drupalCreateUser(['administer smsframework']));
    // Set up default log gateway.
    $this->drupalPostForm('admin/config/smsframework/gateways', ['default' => 'log'], 'Save settings');
    $this->assertResponse(200);
    $gw = sms_default_gateway();
    $this->assertEqual($gw->getIdentifier(), 'log', 'Default gateway set to log.');

    // Set up default test gateway.
    $this->drupalPostForm('admin/config/smsframework/gateways', ['default' => 'test'], 'Save settings');
    $this->assertResponse(200);
    $this->resetAll();
    $gw = sms_default_gateway();
    $this->assertEqual($gw->getIdentifier(), 'test', 'Default gateway set to test.');
  }

  /**
   * Tests configuring a specific gateway.
   */
  public function testGatewayConfiguration() {
    $this->drupalLogin($this->drupalCreateUser(['administer smsframework']));
    $edit = array(
      'username' => 'test',
      'password' => 'testword',
      'server' => 'test.example.com/api',
      'method' => 0,
      'ssl' => false,
    );
    $this->drupalPostForm('admin/config/smsframework/gateways/test', $edit, 'Save configuration');
    $this->assertResponse(200);
    $gateway = $this->gatewayManager->getGateway('test');
    $this->assertEqual($edit, $gateway->getCustomConfiguration(), 'SMS Test gateway successfully configured.');
  }

  /**
   * Tests the sending of messages.
   */
  public function testSendSms() {
    $this->drupalLogin($this->drupalCreateUser(['administer smsframework']));
    $message = 'This is a test message';
    $number = '23412345678';
    $options = array(
      'sender' => 'Sender',
      'gateway' => 'test'
    );

    // Send sms to test gateway.
    $result = sms_send($number, $message, $options);
    $this->assertTrue($result, 'Message successfully sent.');
    $this->assertEqual(sms_test_gateway_result(), array('number' => $number, 'message' => $message, 'options' => $options), 'Message sent to the correct gateway.');
  }

  /**
   * Tests basic number validation.
   */
  public function testNumberValidation() {
    $test_numbers = array(
      '1234567890' => true,
      '123458767890' => true,
      '389427-9238' => true,
      '=-,x2-4n292' => true,
      ';ajklf a/s,MFA' => false,
      '] W[OPQIRW' => false,
      '9996789065' => true,
      '1234567890987654' => true,
    );

    // Test validation with default gateway (log).
    foreach ($test_numbers as $number => $valid) {
      $result = sms_validate_number($number);
      $this->assertEqual($valid, empty($result), 'Number validation ok for ' . $number);
    }
  }

  /**
   * Tests basic number validation.
   */
  public function testNumberValidationWithGateway() {
    $test_numbers = array(
      '1234567890' => true,
      '123458767890' => true,
      '389427-9238' => false,
      '=-,x2-4n292' => false,
      ';ajklf a/s,MFA' => false,
      '] W[OPQIRW' => false,
      '9996789065' => false,
      '1234567890987654' => false,
    );

    foreach ($test_numbers as $number => $valid) {
      $result = sms_validate_number($number, ['gateway' => 'test']);
      $this->assertEqual($valid, empty($result), 'Number validation ok for ' . $number);
    }
  }

}
