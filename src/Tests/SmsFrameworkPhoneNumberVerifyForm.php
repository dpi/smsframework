<?php

/**
 * @file
 * Contains \Drupal\sms\Tests\SmsFrameworkPhoneNumberVerifyForm.
 */

namespace Drupal\sms\Tests;

use Drupal\Core\Url;

/**
 * Tests phone numbers verification code form.
 *
 * @group SMS Framework
 */
class SmsFrameworkPhoneNumberVerifyForm extends SmsFrameworkWebTestBase {

  public static $modules = ['entity_test'];

  /**
   * Test phone number verification form.
   */
  public function testVerifyFormAccess() {
    // Anonymous.
    $this->drupalGet(Url::fromRoute('sms.phone.verify'));
    $this->assertResponse(403);

    // User with permission.
    $account = $this->drupalCreateUser([
      'sms verify phone number',
    ]);
    $this->drupalLogin($account);
    $this->drupalGet(Url::fromRoute('sms.phone.verify'));
    $this->assertResponse(200);
    $this->assertText(t('Verify a phone number'));
    $this->assertText(t('Enter the code you received from a SMS message.'));
  }

  /**
   * Test phone number verification form.
   */
  public function testVerifyForm() {
    $account = $this->drupalCreateUser([
      'sms verify phone number',
    ]);
    $this->drupalLogin($account);

    $this->createEntityWithPhoneNumber(
      $this->createPhoneNumberSettings(),
      ['+123123123']
    );

    $verification = $this->getVerificationCodeLast();
    $code = $verification->getCode();

    $this->assertFalse($verification->getStatus(), 'Phone number verification is not verified.');
    $this->assertFalse(empty($code), 'Verification code is set.');

    // Invalid code.
    $edit['code'] = $this->randomMachineName();
    $this->drupalPostForm(Url::fromRoute('sms.phone.verify'), $edit, t('Verify code'));
    $this->assertText(t('Invalid verification code.'));

    // Valid code.
    $edit['code'] = $code;
    $this->drupalPostForm(Url::fromRoute('sms.phone.verify'), $edit, t('Verify code'));
    $this->assertText(t('Phone number is now verified.'));

    // Reset verification code static cache.
    $this->resetAll();
    $verification = $this->getVerificationCodeLast();
    $this->assertTrue($verification->getStatus(), 'Phone number is verified.');
  }

}
