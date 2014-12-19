<?php

/**
 * @file
 * Contains \Drupal\sms\Tests\SmsFrameworkWebTest.
 */

namespace Drupal\sms\Tests;

use \Drupal\simpletest\WebTestBase;

/**
 * Integration tests for the SMS Framework.
 *
 * @group SMS Framework
 */
class SmsFrameworkWebTest extends WebTestBase {

  public static $modules = ['sms', 'sms_test_gateway'];

  /**
   * Tests that the correct gateways list is obtained.
   */
  public function testGatewaysList() {
    $this->assertEqual(array('log' => t('Log only'), 'test' => t('For testing')), sms_gateways('names'));
  }

  /**
   * Tests setting up the default gateway.
   */
  public function testDefaultGateway() {
    // Test initial default gateway.
    $gw = sms_default_gateway();
    $this->assertEqual($gw['identifier'], 'log', 'Initial default gateway is "log".');

    $this->drupalLogin($this->drupalCreateUser(['administer smsframework']));
    // Set up default log gateway.
    $this->drupalPostForm('admin/config/smsframework/gateways', ['default' => 'log'], t('Set default gateway'));
    $this->assertResponse(200);
    $gw = sms_default_gateway();
    $this->assertEqual($gw['identifier'], 'log', 'Default gateway set to log.');

    // Set up default test gateway.
    $this->drupalPostForm('admin/config/smsframework/gateways', ['default' => 'test'], t('Set default gateway'));
    $this->assertResponse(200);
    $gw = sms_default_gateway();
    $this->assertEqual($gw['identifier'], 'test', 'Default gateway set to test.');
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
    $this->drupalPostForm('admin/config/smsframework/gateways/test', $edit, t('Save'));
    $this->assertResponse(200);
    $gateway = sms_gateways('gateway', 'test');
    $this->assertEqual($edit, $gateway['configuration'], 'SMS Test gateway successfully configured.');
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
