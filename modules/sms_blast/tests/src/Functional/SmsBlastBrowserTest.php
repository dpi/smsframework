<?php

namespace Drupal\Tests\sms_blast\Functional;

use Drupal\Tests\sms\Functional\SmsFrameworkBrowserTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Component\Utility\Unicode;
use Drupal\sms\Entity\PhoneNumberSettings;

/**
 * Integration tests for the sms_blast module.
 *
 * @group SMS Framework
 * @group legacy
 */
class SmsBlastBrowserTest extends SmsFrameworkBrowserTestBase {

  public static $modules = ['sms', 'user', 'sms_blast'];

  /**
   * Phone number settings of user entity type.
   *
   * @var \Drupal\sms\Entity\PhoneNumberSettingsInterface
   */
  protected $phoneNumberSettings;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['Send SMS Blast']));

    $this->gateway = $this->createMemoryGateway(['skip_queue' => TRUE]);
    $this->setFallbackGateway($this->gateway);

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
      ->setVerificationMessage($this->randomString())
      ->save();
  }

  /**
   * Tests sending SMS blast.
   */
  public function testSendBlast() {
    // Create users with multiple phone numbers. Only one message should be sent
    // to each user.
    $phone_numbers = $this->randomPhoneNumbers();
    $entities = [];
    for ($i = 0; $i < 6; $i++) {
      /** @var \Drupal\user\UserInterface $user */
      $user = $this->createEntityWithPhoneNumber($this->phoneNumberSettings, $phone_numbers);
      // Need to activate so when DER does entity validation it is included by the
      // UserSelection plugin.
      $user->activate()->save();
      $entities[] = $user;
    }

    // Verify three of the users randomly.
    $numbers = range(0, count($entities) - 1);
    shuffle($numbers);
    foreach (array_slice($numbers, 0, 3) as $i) {
      $this->verifyPhoneNumber($entities[$i], $phone_numbers[0]);
    }

    // Reset messages created as a result of creating entities above. Such as
    // verification messages.
    $this->resetTestMessages();

    $edit['message'] = $this->randomString();
    $this->drupalPostForm('sms_blast', $edit, t('Send'));
    $this->assertResponse(200);
    $this->assertText('Message sent to 3 users.');

    // Get the resulting message that was sent and confirm.
    $this->assertEqual(3, count($this->getTestMessages($this->gateway)), 'Sent three messages.');
  }

}
