<?php

/**
 * @file
 * Contains \Drupal\sms\Tests\SmsFrameworkPhoneNumberTest.
 */

namespace Drupal\sms\Tests;

use Drupal\sms\Message\SmsMessageInterface;

/**
 * General phone number verification tests.
 *
 * @group SMS Framework
 */
class SmsFrameworkPhoneNumberTest extends SmsFrameworkWebTestBase {

  public static $modules = ['entity_test'];

  /**
   * Ensure phone number verification SMS sent.
   *
   * Tests _sms_entity_postsave()
   */
  public function testPhoneNumberVerificationMessage() {
    $this->defaultSmsProvider->setDefaultGateway($this->testGateway);

    $phone_numbers = ['+123123123'];
    $phone_number_settings = $this->createPhoneNumberSettings();
    $this->createEntityWithPhoneNumber($phone_number_settings, $phone_numbers);

    $sms_message = $this->getLastTestMessage();
    $this->assertTrue($sms_message instanceof SmsMessageInterface, 'SMS verification message sent.');
    $this->assertEqual($sms_message->getRecipients(), $phone_numbers, 'Sent to correct phone number.');

    $phone_verification = $this->getVerificationCodeLast();
    $data['sms_verification_code'] = $phone_verification->getCode();
    $message = \Drupal::token()->replace(
      $phone_number_settings->getVerificationMessage(),
      $data
    );
    $this->assertEqual($sms_message->getMessage(), $message, 'Sent correct message.');
  }

}