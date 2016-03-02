<?php

/**
 * @file
 * Contains tests for the sms_blast module.
 */

namespace Drupal\sms_blast\Tests;

use Drupal\sms\Tests\SmsFrameworkWebTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Component\Utility\Unicode;
use Drupal\sms\Entity\PhoneNumberSettings;

/**
 * Integration tests for the sms_blast module.
 *
 * @group SMS Framework
 */
class SmsBlastWebTest extends SmsFrameworkWebTestBase {

  public static $modules = ['sms_user', 'sms_blast'];

  /**
   * @var \Drupal\sms\Entity\PhoneNumberSettingsInterface
   */
  protected $phoneNumberSettings;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['Send SMS Blast']));

    $this->defaultSmsProvider->setDefaultGateway($this->testGateway);

    $phone_field = FieldStorageConfig::create([
      'entity_type' => 'user',
      'field_name' => Unicode::strtolower($this->randomMachineName()),
      'type' => 'telephone',
    ]);
    $phone_field->save();

    FieldConfig::create([
      'entity_type' => 'user',
      'bundle' => 'user',
      'field_name' => $phone_field->getName(),
    ])->save();

    $this->phoneNumberSettings = PhoneNumberSettings::create();
    $this->phoneNumberSettings
      ->setPhoneNumberEntityTypeId('user')
      ->setPhoneNumberBundle('user')
      ->setFieldName('phone_number', $phone_field->getName())
      ->save();
  }

  /**
   * Tests sending SMS blast.
   */
  function testSendBlast() {
    // Create users with two phone numbers. Only one message should be sent to
    // each user.
    $phone_numbers = ['+123123123', '+456456456'];
    for ($i = 0; $i < 3; $i++) {
      // Create an unverified user.
      $this->createEntityWithPhoneNumber($this->phoneNumberSettings, $phone_numbers);
      // Create a verified user.
      $entity = $this->createEntityWithPhoneNumber($this->phoneNumberSettings, $phone_numbers);
      $this->verifyPhoneNumber($entity, $phone_numbers[0]);
    }
    $this->resetTestMessages();

    $edit['message'] = $this->randomString();
    $this->drupalPostForm('sms_blast', $edit, t('Send'));
    $this->assertResponse(200);
    $this->assertText('Message sent to 3 users.');

    // Get the resulting message that was sent and confirm.
    $this->assertEqual(3, count($this->getTestMessages()), 'Sent three messages.');
  }

}
