<?php

namespace Drupal\sms_user\Tests;

use Drupal\sms\Tests\SmsFrameworkWebTestBase;
use Drupal\Core\Url;

/**
 * Tests SMS User settings user interface.
 *
 * @group SMS User
 */
class SmsFrameworkUserSettingsTest extends SmsFrameworkWebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['sms_user'];

  /**
   * List of days in a week, starting from 'sunday' through to 'saturday'.
   *
   * @var string[]
   */
  protected $days = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $account = $this->drupalCreateUser([
      'administer smsframework',
    ]);
    $this->drupalLogin($account);

    // Build list of days.
    $timestamp = strtotime('next Sunday');
    while (($day = strtolower(strftime('%A', $timestamp))) && !in_array($day, $this->days)) {
      $this->days[] = $day;
      $timestamp = strtotime('+1 day', $timestamp);
    }
  }

  /**
   * Tests saving form and verifying configuration is saved.
   */
  public function testSettingsForm() {
    $this->drupalGet(Url::fromRoute('sms_user.options'));
    $this->assertResponse(200);

    $this->assertFieldByName('active_hours[status]');
    $this->assertNoFieldChecked('edit-active-hours-status');

    // Ensure default select field values.
    foreach ($this->days as $day) {
      $this->assertOptionSelected('edit-active-hours-days-' . $day . '-start', -1);
    }
    foreach ($this->days as $day) {
      $this->assertOptionSelected('edit-active-hours-days-' . $day . '-end', 24);
    }

    $edit = [
      'active_hours[status]' => TRUE,
      'active_hours[days][sunday][start]' => 2,
      'active_hours[days][sunday][end]' => 22,
      'active_hours[days][tuesday][start]' => 0,
      'active_hours[days][tuesday][end]' => 24,
      // This day wont save because start is set to disabled.
      'active_hours[days][thursday][start]' => -1,
      'active_hours[days][thursday][end]' => 18,
    ];
    $this->drupalPostForm(Url::fromRoute('sms_user.options'), $edit, t('Save configuration'));
    $this->assertRaw(t('The configuration options have been saved.'));

    // Check values are saved and form reflects this.
    $this->assertFieldChecked('edit-active-hours-status');
    $this->assertOptionSelected('edit-active-hours-days-sunday-start', 2);
    $this->assertOptionSelected('edit-active-hours-days-sunday-end', 22);
    $this->assertOptionSelected('edit-active-hours-days-tuesday-start', 0);
    $this->assertOptionSelected('edit-active-hours-days-tuesday-end', 24);
    $this->assertOptionSelected('edit-active-hours-days-thursday-start', -1);
    $this->assertOptionSelected('edit-active-hours-days-thursday-end', 24);

    $ranges_expected = [
      ['start' => 'sunday 2:00', 'end' => 'sunday 22:00'],
      ['start' => 'tuesday 0:00', 'end' => 'tuesday +1 day'],
    ];
    $ranges_actual = \Drupal::config('sms_user.settings')
      ->get('active_hours.ranges');
    $this->assertEqual($ranges_expected, $ranges_actual);
  }

  /**
   * Tests saving form with invalid values.
   */
  public function testSettingsFormValidationFail() {
    // End time < start time.
    $edit = [
      'active_hours[days][wednesday][start]' => 10,
      'active_hours[days][wednesday][end]' => 9,
    ];

    $this->drupalPostForm(Url::fromRoute('sms_user.options'), $edit, t('Save configuration'));
    $this->assertRaw(t('End time must be greater than start time.'));

    // Active hours enabled but no days.
    $edit = [
      'active_hours[status]' => TRUE,
      'active_hours[days][wednesday][start]' => -1,
      'active_hours[days][wednesday][end]' => 24,
    ];
    $this->drupalPostForm(Url::fromRoute('sms_user.options'), $edit, t('Save configuration'));
    $this->assertRaw(t('If active hours hours are enabled there must be at least one enabled day.'));
  }

  /**
   * Test account registrations are off.
   */
  public function testAccountRegistrationOff() {
    $edit = [
      'account_registration[behaviour]' => 'none',
    ];
    $this->drupalPostForm(Url::fromRoute('sms_user.options'), $edit, t('Save configuration'));
    $this->assertRaw(t('The configuration options have been saved.'));

    $settings = $this->config('sms_user.settings')->get('account_registration');
    $this->assertFalse($settings['unrecognized_sender']['status']);
    $this->assertFalse($settings['incoming_pattern']['status']);
  }

  /**
   * Test fallback token list for when token.module not available.
   */
  public function testAccountRegistrationReplyTokens() {
    $this->drupalGet(Url::fromRoute('sms_user.options'));
    $this->assertResponse(200);
    $this->assertRaw('Available tokens include: [sms-message:*] [user:*]');
  }

  /**
   * Test account registrations for unrecognised numbers saves to config.
   */
  public function testAccountRegistrationUnrecognised() {
    $this->createPhoneNumberSettings('user', 'user');

    $reply_message = $this->randomString();
    $edit = [
      'account_registration[behaviour]' => 'all',
      'account_registration[all_options][reply_status]' => TRUE,
      'account_registration[all_options][reply][message]' => $reply_message,
    ];
    $this->drupalPostForm(Url::fromRoute('sms_user.options'), $edit, t('Save configuration'));
    $this->assertRaw(t('The configuration options have been saved.'));

    $settings = $this->config('sms_user.settings')->get('account_registration');

    // Status.
    $this->assertTrue($settings['unrecognized_sender']['status']);
    $this->assertFalse($settings['incoming_pattern']['status']);

    // Settings.
    $this->assertTrue($settings['unrecognized_sender']['reply']['status']);
    $this->assertEqual($reply_message, $settings['unrecognized_sender']['reply']['message']);
  }

  /**
   * Test account registrations for incoming pattern saves to config.
   */
  public function testAccountRegistrationIncomingPattern() {
    $this->createPhoneNumberSettings('user', 'user');

    $incoming_message = '[email] ' . $this->randomString();
    $reply_message_success = $this->randomString();
    $reply_message_failure = $this->randomString();
    $edit = [
      'account_registration[behaviour]' => 'incoming_pattern',
      'account_registration[incoming_pattern_options][incoming_message]' => $incoming_message,
      'account_registration[incoming_pattern_options][send_activation_email]' => TRUE,
      'account_registration[incoming_pattern_options][reply_status]' => TRUE,
      'account_registration[incoming_pattern_options][reply][message_success]' => $reply_message_success,
      'account_registration[incoming_pattern_options][reply][message_failure]' => $reply_message_failure,
    ];
    $this->drupalPostForm(Url::fromRoute('sms_user.options'), $edit, t('Save configuration'));
    $this->assertRaw(t('The configuration options have been saved.'));

    $settings = $this->config('sms_user.settings')->get('account_registration');

    // Status.
    $this->assertFalse($settings['unrecognized_sender']['status']);
    $this->assertTrue($settings['incoming_pattern']['status']);

    // Settings.
    $this->assertEqual($incoming_message, $settings['incoming_pattern']['incoming_messages'][0]);
    $this->assertTrue($settings['incoming_pattern']['send_activation_email']);
    $this->assertTrue($settings['incoming_pattern']['reply']['status']);
    $this->assertEqual($reply_message_success, $settings['incoming_pattern']['reply']['message']);
    $this->assertEqual($reply_message_failure, $settings['incoming_pattern']['reply']['message_failure']);
  }

  /**
   * Test account registrations validation failures on empty replies.
   */
  public function testAccountRegistrationValidationEmptyReplies() {
    $this->createPhoneNumberSettings('user', 'user');

    $edit = [
      'account_registration[behaviour]' => 'all',
      'account_registration[all_options][reply_status]' => TRUE,
      'account_registration[all_options][reply][message]' => '',
    ];
    $this->drupalPostForm(Url::fromRoute('sms_user.options'), $edit, t('Save configuration'));
    $this->assertRaw('Reply message must have a value if reply is enabled.', 'Validation failed for message on all unrecognised numbers when reply status is enabled.');

    $edit = [
      'account_registration[behaviour]' => 'incoming_pattern',
      'account_registration[incoming_pattern_options][reply_status]' => TRUE,
      'account_registration[incoming_pattern_options][reply][message_success]' => '',
    ];
    $this->drupalPostForm(Url::fromRoute('sms_user.options'), $edit, t('Save configuration'));
    $this->assertRaw('Reply message must have a value if reply is enabled.', 'Validation failed for message_success on incoming_pattern when reply status is enabled.');

    $edit = [
      'account_registration[behaviour]' => 'incoming_pattern',
      'account_registration[incoming_pattern_options][reply_status]' => TRUE,
      'account_registration[incoming_pattern_options][reply][message_failure]' => '',
    ];
    $this->drupalPostForm(Url::fromRoute('sms_user.options'), $edit, t('Save configuration'));
    $this->assertRaw('Reply message must have a value if reply is enabled.', 'Validation failed for message_failure on incoming_pattern when reply status is enabled.');
  }

  /**
   * Test account registrations validation failures on empty replies.
   */
  public function testAccountRegistrationValidationIncomingPattern() {
    $this->createPhoneNumberSettings('user', 'user');

    $edit = [
      'account_registration[behaviour]' => 'incoming_pattern',
      'account_registration[incoming_pattern_options][incoming_message]' => '',
    ];
    $this->drupalPostForm(Url::fromRoute('sms_user.options'), $edit, t('Save configuration'));
    $this->assertRaw('Incoming message must be filled if using pre-incoming_pattern option');

    $edit = [
      'account_registration[behaviour]' => 'incoming_pattern',
      'account_registration[incoming_pattern_options][send_activation_email]' => TRUE,
      'account_registration[incoming_pattern_options][incoming_message]' => $this->randomString(),
    ];
    $this->drupalPostForm(Url::fromRoute('sms_user.options'), $edit, t('Save configuration'));
    $this->assertRaw('Activation email cannot be sent if [email] placeholder is missing.');

    $edit = [
      'account_registration[behaviour]' => 'incoming_pattern',
      'account_registration[incoming_pattern_options][send_activation_email]' => TRUE,
      'account_registration[incoming_pattern_options][incoming_message]' => 'E [email] P [password]',
    ];
    $this->drupalPostForm(Url::fromRoute('sms_user.options'), $edit, t('Save configuration'));
    $this->assertRaw('Activation email cannot be sent if [password] placeholder is present.');

    // Placeholder seperation.
    // Tests separator so regex doesn't have problems.
    $edit = [
      'account_registration[behaviour]' => 'incoming_pattern',
      'account_registration[incoming_pattern_options][incoming_message]' => 'Email [email][password]',
    ];
    $this->drupalPostForm(Url::fromRoute('sms_user.options'), $edit, t('Save configuration'));
    $this->assertRaw('There must be a separator between placeholders.');
  }

  /**
   * Test form state when no phone number settings exist for user entity type.
   *
   * Tests notice is displayed and some form elements are disabled.
   */
  public function testFormNoUserPhoneNumberSettings() {
    $this->drupalGet(Url::fromRoute('sms_user.options'));
    $this->assertRaw(t('There are no phone number settings configured for the user entity type. Some features cannot operate without these settings. <a href=":add">Add phone number settings</a>.', [
      ':add' => Url::fromRoute('entity.phone_number_settings.add')->toString(),
    ]), 'Warning message displayed for no phone number settings.');

    $input = $this->xpath('//input[@name="account_registration[behaviour]" and @disabled="disabled" and @value="all"]');
    $this->assertTrue(count($input) === 1, "The 'All unrecognised phone numbers' radio is disabled.");

    $input = $this->xpath('//input[@name="account_registration[behaviour]" and @disabled="disabled" and @value="incoming_pattern"]');
    $this->assertTrue(count($input) === 1, "The 'incoming_pattern' radio is disabled.");
  }

  /**
   * Test form state when phone number settings exist for user entity type.
   *
   * Tests notice is not displayed and form elements are not disabled.
   */
  public function testFormUserPhoneNumberSettings() {
    $this->createPhoneNumberSettings('user', 'user');
    $this->drupalGet(Url::fromRoute('sms_user.options'));
    $this->assertNoRaw(t('There are no phone number settings configured for the user entity type. Some features cannot operate without these settings.'), 'Warning message displayed for no phone number settings.');

    $input = $this->xpath('//input[@name="account_registration[behaviour]" and @disabled="disabled" and @value="all"]');
    $this->assertTrue(count($input) === 0, "The 'All unrecognised phone numbers' radio is not disabled.");

    $input = $this->xpath('//input[@name="account_registration[behaviour]" and @disabled="disabled" and @value="incoming_pattern"]');
    $this->assertTrue(count($input) === 0, "The 'incoming_pattern' radio is not disabled.");
  }

}
