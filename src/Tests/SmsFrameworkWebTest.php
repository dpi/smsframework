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
    // Test that gateways are correctly discovered.
    // 'log' from module install. 'test_gateway' from test setup.
    $this->assertEqual(
      array_keys(SmsGateway::loadMultiple()),
      ['log', $this->testGateway->id()]
    );
  }

  /**
   * Tests the Gateway list implementation.
   */
  public function testGatewayList() {
    $this->drupalLogin($this->drupalCreateUser(['administer smsframework']));

    $this->drupalGet('admin/config/smsframework/gateways');
    $this->assertResponse(200);

    $this->assertRaw('<td>Drupal log</td>');
    $this->assertRaw('<td>Memory</td>');
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
    $this->assertEqual(5, count(SmsGateway::loadMultiple()));
  }

  /**
   * Tests setting up the default gateway.
   */
  public function testDefaultGateway() {
    // Test initial default gateway.
    $sms_gateway_default = $this->defaultSmsProvider->getDefaultGateway();

    $this->assertEqual($sms_gateway_default->id(), 'log', 'Initial default gateway is "log".');

    $this->drupalLogin($this->drupalCreateUser(['administer smsframework']));

    // Change default gateway.
    $this->drupalPostForm('admin/config/smsframework/default-gateway', [
      'default_gateway' => $this->testGateway->id(),
    ], 'Save configuration');
    $this->assertResponse(200);

    $sms_gateway_default = $this->defaultSmsProvider->getDefaultGateway();
    $this->assertEqual($sms_gateway_default->id(), $this->testGateway->id(), 'Default gateway changed.');
  }

  /**
   * Tests configuring a specific gateway.
   */
  public function testGatewayConfiguration() {
    $this->drupalLogin($this->drupalCreateUser(['administer smsframework']));

    $this->drupalGet('admin/config/smsframework/gateways/' . $this->testGateway->id());
    $this->assertResponse(200);


    $edit = array(
      'widget' => 'FooBar',
    );
    $this->drupalPostForm('admin/config/smsframework/gateways/' . $this->testGateway->id(), $edit, 'Save');
    $this->assertResponse(200);

    // Reload the gateway.
    $this->testGateway = SmsGateway::load($this->testGateway->id());
    // Check the entity that config was changed.
    $config = $this->testGateway->getPlugin()->getConfiguration();
    $this->assertEqual($edit, $config, 'Config changed.');
  }

  /**
   * Tests the sending of messages.
   */
  public function testSendSms() {
    $this->drupalLogin($this->drupalCreateUser(['administer smsframework']));

    // Ensure default gateway is different to test_gateway.
    $this->assertNotEqual($this->defaultSmsProvider->getDefaultGateway(), $this->testGateway->id());

    $message = 'This is a test message';
    $number = '23412345678';
    $options = [
      'sender' => 'Sender',
      'gateway' => $this->testGateway->id()
    ];

    // Send sms to test gateway.
    $pre_count = count($this->getTestMessages());
    $result = sms_send($number, $message, $options);
    $this->assertTrue($result, 'Message successfully sent.');

    $this->assertTrue(count($this->getTestMessages()) > $pre_count, 'Message sent to the correct gateway.'
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
//  public function testNumberValidationWithGateway() {
//    // @todo, reimplement number validation. TBD.
//    $test_numbers = array(
//      '1234567890' => true,
//      '123458767890' => true,
//      '389427-9238' => false,
//      '=-,x2-4n292' => false,
//      ';ajklf a/s,MFA' => false,
//      '] W[OPQIRW' => false,
//      '9996789065' => false,
//      '1234567890987654' => false,
//    );
//
//    foreach ($test_numbers as $number => $valid) {
//      $result = sms_validate_number($number, ['gateway' => 'test']);
//      $this->assertEqual($valid, empty($result), 'Number validation ok for ' . $number);
//    }
//  }

}
