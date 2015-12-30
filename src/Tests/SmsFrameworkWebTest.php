<?php

/**
 * @file
 * Contains \Drupal\sms\Tests\SmsFrameworkWebTest.
 */

namespace Drupal\sms\Tests;

use Drupal\sms\Entity\SmsGateway;

/**
 * Integration tests for the SMS Framework.
 *
 * @group SMS Framework
 */
class SmsFrameworkWebTest extends SmsFrameworkWebTestBase {

  /**
   * Tests the HookGateway implementation.
   */
  public function testGatewayConfigIntegration() {
    // Test that gateway plugins are correctly discovered.
    $gateway_plugins = SmsGateway::loadMultiple();

    // 'log' from module install. 'test_gateway' from test setup.
    $this->assertEqual(array_keys($gateway_plugins), ['log', 'test_gateway']);
  }

  /**
   * Tests the add gateway functionality.
   */
  public function testAddGateways() {
    for ($i = 0; $i < 3; $i++) {
      $sms_gateway = SmsGateway::create([
        'plugin' => 'log',
        'id' => $this->randomMachineName(),
        'label' => $this->randomString(),
      ]);
      $sms_gateway->save();
    }
    $this->assertEqual(5, SmsGateway::loadMultiple());
  }

  /**
   * Tests setting up the default gateway.
   */
  public function testDefaultGateway() {
    // Test initial default gateway.
    $sms_gateway_default = $this->gatewayManager->getDefaultGateway();

    $this->assertEqual($sms_gateway_default->id(), 'log', 'Initial default gateway is "log".');

    $this->drupalLogin($this->drupalCreateUser(['administer smsframework']));

    // Change default gateway.
    $this->drupalPostForm('admin/config/smsframework/gateways/' . $this->test_gateway->id(), [
      'site_default' => TRUE,
    ], 'Save');
    $this->assertResponse(200);

    $sms_gateway_default = $this->gatewayManager->getDefaultGateway();
    $this->assertEqual($sms_gateway_default->id(), $this->test_gateway->id(), 'Default gateway changed.');
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
    $options = [
      'sender' => 'Sender',
      'gateway' => $this->test_gateway->id()
    ];

    // Send sms to test gateway.
    $result = sms_send($number, $message, $options);
    $this->assertTrue($result, 'Message successfully sent.');
    $this->assertEqual(
      sms_test_gateway_result(),
      ['number' => $number, 'message' => $message, 'options' => $options],
      'Message sent to the correct gateway.'
    );
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
