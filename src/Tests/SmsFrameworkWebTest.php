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
