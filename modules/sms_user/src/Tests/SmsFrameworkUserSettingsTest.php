<?php

/**
 * @file
 * Contains \Drupal\sms_user\Tests\SmsFrameworkUserSettingsTest.
 */

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
   * Tests sms_user admin options.
   */
  public function testSettingsGeneralForm() {
    // Set the sms_user admin options.
    $edit = [
      'registration_enabled' => 1,
      'allow_password' => 1,
      'new_account_message' => $this->randomString(30),
    ];
    $this->drupalPostForm(Url::fromRoute('sms_user.options'), $edit, t('Save configuration'));
    $this->assertResponse(200);

    // Verify that the variables are set.
    foreach ($edit as $variable_name => $expected) {
      $actual = $this->config('sms_user.settings')->get($variable_name);
      $this->assertEqual($expected, $actual, sprintf('Variable %s has been set.', $variable_name));
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

}
