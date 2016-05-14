<?php

/**
 * @file
 * Contains \Drupal\sms\Tests\SmsFrameworkWebTest.
 */

namespace Drupal\sms\Tests;

use Drupal\Core\Url;
use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Direction;

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

  /**
   * Tests queue statistics located on Drupal report page.
   */
  public function testQueueReport() {
    /** @var \Drupal\sms\Provider\SmsProviderInterface $provider */
    $provider = \Drupal::service('sms_provider');

    /** @var \Drupal\sms\Entity\SmsMessageInterface $sms_message */
    $sms_message = SmsMessage::create();
    $sms_message
      ->setMessage($this->randomString())
      ->addRecipients($this->randomPhoneNumbers());

    for ($i = 0; $i < 2; $i++) {
      $clone = $sms_message->createDuplicate()
        ->setDirection(Direction::INCOMING);
      $provider->queue($clone);
    }
    for ($i = 0; $i < 4; $i++) {
      $clone = $sms_message->createDuplicate()
        ->setDirection(Direction::OUTGOING);
      $provider->queue($clone);
    }

    $this->drupalLogin($this->rootUser);
    $this->drupalGet(Url::fromRoute('system.status'));

    $this->assertRaw('There are 2 messages in the incoming queue.');
    $this->assertRaw('There are 4 messages in the outgoing queue.');
  }

}
