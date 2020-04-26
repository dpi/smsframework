<?php

namespace Drupal\Tests\sms\Functional;

use Drupal\Core\Url;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests phone numbers.
 *
 * @group SMS Framework
 */
class SmsFrameworkPhoneNumberWidgetTest extends SmsFrameworkBrowserTestBase {

  use CronRunTrait;

  public static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $account = $this->drupalCreateUser([
      'sms verify phone number',
      // Required to edit entity_test.
      'administer entity_test content',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Test telephone widget using entity form.
   */
  public function testPhoneNumberWidget() {
    $phone_number_settings = $this->createPhoneNumberSettings('entity_test', 'entity_test');
    $field_phone_number = $phone_number_settings->getFieldName('phone_number');
    $form_field_phone_number = $field_phone_number . '[0][value]';

    $test_entity = $this->createEntityWithPhoneNumber($phone_number_settings);

    // No verification code created.
    $this->drupalGet($test_entity->toUrl('edit-form'));
    $t_args = [
      '@url' => Url::fromRoute('sms.phone.verify')->toString(),
      '@time' => '1 hour',
    ];
    $this->assertRaw(t('Enter a phone number. A verification code will be sent as an SMS message, you must enter the code into the <a href="@url">verification form</a> within @time.', $t_args));

    // Create verification code, wait for confirmation.
    $edit = [
      $form_field_phone_number => '+123123123',
    ];
    $this->drupalPostForm($test_entity->toUrl('edit-form'), $edit, t('Save'));

    $this->assertRaw(t('A verification code has been sent to this phone number. Go to the <a href="@url">verification form</a> and enter the code. The code will expire if it is not verified in', $t_args));

    $input = $this->xpath('//input[@name="' . $form_field_phone_number . '" and @disabled="disabled"]');
    $this->assertTrue(count($input) === 1, 'The phone number text field is disabled.');

    // Verify the code.
    $phone_verification = $this->getLastVerification();
    $phone_verification
      ->setStatus(TRUE)
      ->save();

    // Check phone number is verified.
    $this->drupalGet($test_entity->toUrl('edit-form'));
    $this->assertRaw(t('This phone number is verified. <strong>Warning:</strong> Modifying this phone number will remove verification.'));

    $input = $this->xpath('//input[@name="' . $form_field_phone_number . '" and @disabled="disabled"]');
    $this->assertTrue(count($input) === 0, 'The phone number text field is enabled.');
  }

  /**
   * Test behavior of widget when verification code expires.
   */
  public function testPhoneNumberWidgetWithExpiredVerificationCode() {
    $phone_number_settings = $this->createPhoneNumberSettings('entity_test', 'entity_test');
    $test_entity = $this->createEntityWithPhoneNumber($phone_number_settings, ['+123123123']);

    // Force verification code to expire.
    $phone_verification = $this->getLastVerification();
    $phone_verification
      ->set('created', time() - ($phone_number_settings->getVerificationCodeLifetime() + 1))
      ->save();

    $this->drupalGet($test_entity->toUrl('edit-form'));
    $this->assertRaw(t('Verification code expired. Try again later.'));

    $this->cronRun();
    $this->drupalGet($test_entity->toUrl('edit-form'));

    $t_args = [
      '@url' => Url::fromRoute('sms.phone.verify')->toString(),
      '@time' => '1 hour',
    ];

    // Ensure phone number was purged.
    $field_phone_number = $phone_number_settings->getFieldName('phone_number');
    $this->assertFieldByName($field_phone_number . '[0][value]', '');
    $this->assertRaw(t('Enter a phone number. A verification code will be sent as an SMS message, you must enter the code into the <a href="@url">verification form</a> within @time.', $t_args));
  }

  /**
   * Test behaviour of widget with phone number purge setting.
   */
  public function testPhoneNumberPurgedFieldValueOnExpiration() {
    $phone_number_settings = $this->createPhoneNumberSettings('entity_test', 'entity_test');
    $phone_number_settings
      ->setPurgeVerificationPhoneNumber(TRUE)
      ->save();
    $test_entity = $this->createEntityWithPhoneNumber($phone_number_settings, ['+123123123']);

    // Force verification code to expire.
    $this->getLastVerification()
      ->set('created', time() - ($phone_number_settings->getVerificationCodeLifetime() + 1))
      ->save();
    $this->cronRun();

    // Ensure phone number value was removed from the field.
    $this->drupalGet($test_entity->toUrl('edit-form'));
    $field_phone_number = $phone_number_settings->getFieldName('phone_number');
    $this->assertFieldByName($field_phone_number . '[0][value]', '');
  }

  /**
   * Test behaviour of widget with phone number purge setting.
   */
  public function testPhoneNumberNotPurgedFieldValueOnExpiration() {
    $phone_number_settings = $this->createPhoneNumberSettings('entity_test', 'entity_test');
    $phone_number_settings
      ->setPurgeVerificationPhoneNumber(FALSE)
      ->save();
    $test_entity = $this->createEntityWithPhoneNumber($phone_number_settings, ['+123123123']);

    // Force verification code to expire.
    $this->getLastVerification()
      ->set('created', time() - ($phone_number_settings->getVerificationCodeLifetime() + 1))
      ->save();
    $this->cronRun();

    // Ensure phone number value is still on the field.
    $this->drupalGet($test_entity->toUrl('edit-form'));
    $t_args = [
      '@url' => Url::fromRoute('sms.phone.verify')->toString(),
      '@time' => '1 hour',
    ];
    $this->assertRaw(t('Save this form to send a new verification code as an SMS message, you must enter the code into the <a href="@url">verification form</a> within @time.', $t_args));
    $field_phone_number = $phone_number_settings->getFieldName('phone_number');
    $this->assertFieldByName($field_phone_number . '[0][value]', '+123123123');
  }

  // @todo test multi cardinality phone field.
}
