<?php

/**
 * @file
 * Contains \Drupal\sms_user\Tests\SmsUserWebTest.
 */

namespace Drupal\sms_user\Tests;

use Drupal\sms\Tests\SmsFrameworkWebTestBase;
use Drupal\user\Entity\User;

/**
 * Integration tests for SMS User module.
 *
 * @group SMS User
 *
 * @todo Add tests for creation of users via sms.
 * @todo Add tests for integration with rules and actions modules.
 */
class SmsUserWebTest extends SmsFrameworkWebTestBase {

  public static $modules = ['sms_user', 'syslog', 'sms_devel'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->gateway = $this->createMemoryGateway(['skip_queue' => TRUE]);
    $this->defaultSmsProvider
      ->setDefaultGateway($this->gateway);
  }

  /**
   * Tests sms_user admin options.
   *
   * @todo Transition this to SmsFrameworkUserSettingsTest.
   */
  public function testSmsUserOptions() {
    $user = $this->drupalCreateUser(array('administer smsframework'));
    $this->drupalLogin($user);

    // Set the sms_user admin options.
    $edit = array(
      'registration_enabled' => 1,
      'allow_password' => 1,
      'new_account_message' => $this->randomString(30),
    );
    $this->drupalPostForm('admin/config/smsframework/sms_user_options', $edit, t('Save configuration'));
    $this->assertResponse(200);

    // Verify that the variables are set.
    foreach ($edit as $name => $value) {
      $this->assertEqual($value, $this->config('sms_user.settings')->get($name), sprintf('Variable %s has been set.', $name));
    }
  }

  /**
   * Tests whether a user can opt out and in for sms messages from the site.
   */
  public function testSmsUserOptOut() {
    // Create Excluded User
    $excluded_user = $this->drupalCreateUser(array('administer smsframework'));
    $this->drupalLogin($excluded_user);

    $sms_user_settings = array(
      'registration_enabled' => TRUE,
      'allow_password' => TRUE,
    );
    $this->drupalPostForm('admin/config/smsframework/sms_user_options', $sms_user_settings, t('Save configuration'));

    // Confirm excluded_user number.
    $edit = array('number' => '1234567890');
    $this->drupalPostForm('user/' . $excluded_user->id() . '/mobile', $edit, t('Confirm number'));
    $this->drupalPostForm(NULL, NULL, t('Confirm without code'));
    $this->assertText('Your mobile phone number has been confirmed.', 'Authors number is confirmed');

    // Set the Opt Out checkbox.
    $opt_out = array('opted_out' => TRUE );
    $this->drupalPostForm('user/' . $excluded_user->id() . '/mobile', $opt_out, t('Set'));
    $this->assertText(t('The changes have been saved.'), 'Excluded user has chosen to opt out of messages from the site.');

    $test_message1 = array(
      'number' => '1234567890',
      'message' => 'Test opting out of messages',
    );

    $this->resetTestMessages();
    $this->drupalPostForm('admin/config/smsframework/devel', $test_message1, t('Send Message'));
    $this->assertResponse(200);
    // Test if the message was not sent by checking the cached sms_test message
    // result.

    $sms_messages = $this->getTestMessages($this->gateway);
//    $this->assertTrue(empty($sms_messages),  t('Message was not sent to user that opted out.'));

    // Create Normal User
    $normal_user = $this->drupalCreateUser(array('administer smsframework'));
    $this->drupalLogin($normal_user);

    // Confirm normal_user number.
    $edit = array('number' => '0987654321');
    $this->drupalPostForm('user/' . $normal_user->id() . '/mobile', $edit, t('Confirm number'));
    $this->drupalPostForm(NULL, NULL, t('Confirm without code'));
    $this->assertText('Your mobile phone number has been confirmed.', 'Authors number is confirmed');

    // Set the Opt Out checkbox.
    $setting = array('opted_out' => FALSE );
    $this->drupalPostForm('user/' . $normal_user->id() . '/mobile', $setting, t('Set'));
    $this->assertText(t('The changes have been saved.'), t('Author has chosen opt in for messages from the site.'));

    $test_message2 = array(
      'number' => '0987654321',
      'message' => 'Test opting in for messages.',
    );

    $this->resetTestMessages();
    $this->drupalPostForm('admin/config/smsframework/devel', $test_message2, t('Send Message'));
    $this->assertResponse(200);
    $this->assertText('Form submitted ok for number ' . $test_message2['number'] . ' and message: ' . $test_message2['message'], 'Successfully sent message to recipient with registered number');

    // Test if the message was not sent by checking the cached sms_test message
    // result.

    $sms_message = $this->getLastTestMessage($this->gateway);
    $this->assertTrue(in_array($test_message2['number'], $sms_message->getRecipients()),  t('Message was sent to user that did not opt out.'));

    // Disable Opt Out for this site.
    $this->drupalLogin($excluded_user);
    $sms_user_settings['allow_opt_out'] = FALSE;
    $this->drupalPostForm('admin/config/smsframework/sms_user_options', $sms_user_settings, t('Save configuration'));
    $this->assertFalse($this->config('sms_user.settings')->get('allow_opt_out'), 'Opt out globally disabled.');

    // Confirm that the opt-out button is not available to users.
    $this->drupalGet('user/' . $excluded_user->id() . '/mobile');
    $this->assertNoText(t('Opt out of sms messages from this site.'), t('Opt out checkbox not visible in UI.'));

    // Ensure opt out doesn't work when message is sent.
    $this->resetTestMessages();
    $this->drupalPostForm('admin/config/smsframework/devel', $test_message1, t('Send Message'));
    $this->assertResponse(200);
    $this->assertText('Form submitted ok for number ' . $test_message1['number'] . ' and message: ' . $test_message1['message'], 'Successfully sent message to recipient with registered number');

    $sms_message = $this->getLastTestMessage($this->gateway);
    $this->assertTrue(in_array($test_message1['number'], $sms_message->getRecipients()),  t('Message was sent to user who opted out due to global override.'));
  }

}
