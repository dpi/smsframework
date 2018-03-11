<?php

namespace Drupal\Tests\sms\Functional;

use Drupal\Core\Url;

/**
 * Tests phone numbers verification code form.
 *
 * @group SMS Framework
 */
class SmsFrameworkPhoneNumberVerifyForm extends SmsFrameworkBrowserTestBase {

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
      $this->createPhoneNumberSettings('entity_test', 'entity_test'),
      ['+123123123']
    );

    $verification = $this->getLastVerification();
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
    $verification = $this->getLastVerification();
    $this->assertTrue($verification->getStatus(), 'Phone number is verified.');
  }

  /**
   * Test phone number verification form.
   */
  public function testVerifyFormFlood() {
    // Reduce number of POST requests. Number isn't important.
    \Drupal::configFactory()->getEditable('sms.settings')
      ->set('flood.verify_limit', 1)
      ->save();

    $account = $this->drupalCreateUser([
      'sms verify phone number',
    ]);
    $this->drupalLogin($account);

    $edit['code'] = $this->randomMachineName();
    $this->drupalPostForm(Url::fromRoute('sms.phone.verify'), $edit, t('Verify code'));
    $this->assertNoText(t('There has been too many failed verification attempts. Try again later.'));
    $this->drupalPostForm(Url::fromRoute('sms.phone.verify'), $edit, t('Verify code'));
    $this->assertText(t('There has been too many failed verification attempts. Try again later.'));
  }

  /**
   * Test changing verification path.
   */
  public function testVerifyPathSettings() {
    $account = $this->drupalCreateUser([
      'sms verify phone number',
      'administer smsframework',
    ]);
    $this->drupalLogin($account);

    // Hard code path, don't use Url::fromRoute.
    $this->drupalGet('/verify');
    $this->assertResponse(200, 'Default phone number verification route exists at /verify');

    $path_verify = '/' . $this->randomMachineName() . '/' . $this->randomMachineName();
    $edit = [
      'pages[verify]' => $path_verify,
    ];
    $this->drupalPostForm(Url::fromRoute('sms.settings'), $edit, 'Save configuration');

    // Ensure the route cache is rebuilt by getting the verify route.
    $this->drupalGet($path_verify);
    $this->assertResponse(200, 'Phone number verification route changed to ' . $path_verify);
    $this->drupalGet('/verify');
    $this->assertResponse(404, 'Previous route path was invalidated.');
  }

}
