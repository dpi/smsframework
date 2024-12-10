<?php

declare(strict_types=1);

namespace Drupal\Tests\sms\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\sms\Entity\PhoneNumberSettings;
use Drupal\sms\Entity\PhoneNumberSettingsInterface;
use Drupal\sms\Plugin\Field\FieldWidget\SmsTelephoneWidget;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\sms\Trait\SmsFrameworkTestTrait;

/**
 * Base test class for functional browser tests.
 *
 * Provides commonly used functionality for tests.
 *
 * @method \Drupal\user\UserInterface drupalCreateUser(array $permissions = [], $name = NULL, $admin = FALSE, array $values = []) {
 */
abstract class SmsFrameworkBrowserTestBase extends BrowserTestBase {

  use SmsFrameworkTestTrait;

  protected static $modules = [
    'sms',
    'sms_test_gateway',
    'telephone',
    'dynamic_entity_reference',
  ];

  protected $defaultTheme = 'stark';

  /**
   * Utility to create phone number settings.
   */
  protected function createPhoneNumberSettings(string $entity_type_id, string $bundle): PhoneNumberSettingsInterface {
    $field_storage = FieldStorageConfig::create([
      'entity_type' => $entity_type_id,
      'field_name' => \mb_strtolower($this->randomMachineName()),
      'type' => 'telephone',
    ]);
    $field_storage
      ->setCardinality(1)
      ->save();

    FieldConfig::create([
      'entity_type' => $entity_type_id,
      'bundle' => $bundle,
      'field_name' => $field_storage->getName(),
    ])->save();

    $entity_form_display = EntityFormDisplay::load($entity_type_id . '.' . $bundle . '.default') ?? EntityFormDisplay::create([
      'targetEntityType' => $entity_type_id,
      'bundle' => $bundle,
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $entity_form_display->save();

    $entity_form_display
      ->setComponent($field_storage->getName(), [
        'type' => SmsTelephoneWidget::PLUGIN_ID,
      ])
      ->save();

    $settings = PhoneNumberSettings::create()
      ->setFieldName('phone_number', $field_storage->getName())
      ->setPhoneNumberEntityTypeId($entity_type_id)
      ->setPhoneNumberBundle($bundle)
      ->setVerificationCodeLifetime(3601)
      ->setVerificationMessage('Verification code is [sms:verification-code]')
      ->setPurgeVerificationPhoneNumber(TRUE);
    $settings->save();

    return $settings;
  }

}
