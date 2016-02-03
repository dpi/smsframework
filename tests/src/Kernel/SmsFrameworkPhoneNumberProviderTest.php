<?php

/**
 * @file
 * Contains \Drupal\Tests\sms\Kernel\SmsFrameworkPhoneNumberProviderTest.
 */

namespace Drupal\Tests\sms\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\sms\Entity\PhoneNumberSettings;
use Drupal\Component\Utility\Unicode;
use Drupal\sms\Entity\PhoneNumberSettingsInterface;

/**
 * Tests Phone Number Provider.
 *
 * @group SMS Framework
 * @coversDefaultClass \Drupal\sms\Provider\PhoneNumberProvider
 */
class SmsFrameworkPhoneNumberProviderTest extends SmsFrameworkKernelBase {

  /**
   * @var \Drupal\sms\Provider\PhoneNumberProviderInterface
   */
  protected $phoneNumberProvider;

  /**
   * @var \Drupal\field\FieldStorageConfigInterface
   */
  protected $phoneField;

  /**
   * @var \Drupal\sms\Entity\PhoneNumberSettingsInterface
   */
  protected $phoneNumberSettings;

  /**
   * Storage for Phone Number Verification entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $phoneNumberVerificationStorage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['sms', 'entity_test', 'user', 'field', 'telephone', 'dynamic_entity_reference'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('sms_phone_number_verification');
    $this->installConfig('sms');

    $this->phoneNumberProvider = $this->container->get('sms.phone_number');
    $this->phoneNumberVerificationStorage = $this->container->get('entity_type.manager')
      ->getStorage('sms_phone_number_verification');

    $this->phoneField = FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => Unicode::strtolower($this->randomMachineName()),
      'type' => 'telephone',
    ]);
    $this->phoneField->save();

    FieldConfig::create([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field_name' => $this->phoneField->getName(),
    ])->save();

    $this->phoneNumberSettings = PhoneNumberSettings::create();
    $this->phoneNumberSettings
      ->setPhoneNumberEntityTypeId('entity_test')
      ->setPhoneNumberBundle('entity_test')
      ->setFieldName('phone_number', $this->phoneField->getName())
      ->save();
  }

  /**
   * Tests unverified phone numbers
   *
   * @covers ::getPhoneNumbers
   */
  public function testGetPhoneNumbers() {
    $phone_numbers_all = ['+123123123', '+456456456'];

    // Test zero, one, multiple phone numbers.
    for ($i = 0; $i < 3; $i++) {
      $phone_numbers = array_slice($phone_numbers_all, 0, $i);
      $entity = $this->createEntityWithPhoneNumber($this->phoneNumberSettings, $phone_numbers);

      $return = $this->phoneNumberProvider->getPhoneNumbers($entity, NULL);
      $this->assertEquals($phone_numbers, $return);

      $return = $this->phoneNumberProvider->getPhoneNumbers($entity, FALSE);
      $this->assertEquals($phone_numbers, $return);

      $return = $this->phoneNumberProvider->getPhoneNumbers($entity, TRUE);
      $this->assertEquals([], $return);

      // Verify first phone number.
      // Ensures test verifications don't leak between entities.
      // array_slice()' $preserve_keys ensures original field index is retained.
      $phone_number_verified = array_slice($phone_numbers, 0, 1, TRUE);
      $phone_number_unverified = array_slice($phone_numbers, 1, $i, TRUE);

      if (!empty($phone_number_verified))  {
        $verifications = $this->phoneNumberVerificationStorage
          ->loadByProperties([
            'phone' => reset($phone_number_verified),
            'entity__target_id' => $entity->id(),
          ]);

        $verification = reset($verifications);
        $verification->setStatus(TRUE)
          ->save();
      }

      $return = $this->phoneNumberProvider->getPhoneNumbers($entity, NULL);
      $this->assertEquals($phone_numbers, $return);

      $return = $this->phoneNumberProvider->getPhoneNumbers($entity, FALSE);
      $this->assertEquals($phone_number_unverified, $return, $entity->id());

      $return = $this->phoneNumberProvider->getPhoneNumbers($entity, TRUE);
      $this->assertEquals($phone_number_verified, $return);
    }
  }

}