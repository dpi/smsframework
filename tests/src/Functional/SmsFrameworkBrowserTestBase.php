<?php

declare(strict_types=1);

namespace Drupal\Tests\sms\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\sms\Plugin\Field\FieldWidget\SmsTelephoneWidget;
use Drupal\Tests\BrowserTestBase;

/**
 * Base test class for functional browser tests.
 *
 * Provides commonly used functionality for tests.
 *
 * @method \Drupal\user\UserInterface drupalCreateUser(array $permissions = [], $name = NULL, $admin = FALSE, array $values = []) {
 */
abstract class SmsFrameworkBrowserTestBase extends BrowserTestBase {

  protected static $modules = [
    'another_entity_iterator',
    'bca',
    'dynamic_entity_reference',
    'field',
    'notifier',
    'sms',
    'sms',
    'telephone',
    'user',
  ];

  protected $defaultTheme = 'stark';

  /**
   * Utility to create phone number settings.
   */
  protected function createPhoneNumberSettings(string $entityTypeId, string $bundle, ?string $fieldName): void {
    $fieldName ??= \strtolower($this->randomMachineName());
    $this->createPhoneNumberField($entityTypeId, $bundle, $fieldName);

    $entityFormDisplay = EntityFormDisplay::load(\sprintf('%s.%s.default', $entityTypeId, $bundle)) ?? EntityFormDisplay::create([
      'targetEntityType' => $entityTypeId,
      'bundle' => $bundle,
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $entityFormDisplay->save();

    $entityFormDisplay
      ->setComponent($fieldName, [
        'type' => SmsTelephoneWidget::PLUGIN_ID,
      ])
      ->save();
  }

  protected function createPhoneNumberField(string $entityTypeId, string $bundle, ?string $fieldName): FieldConfigInterface {
    $fieldName ??= \mb_strtolower($this->randomMachineName());
    $storage = FieldStorageConfig::create([
      'entity_type' => $entityTypeId,
      'field_name' => $fieldName,
      'type' => 'telephone',
    ]);
    $storage->save();

    $config = FieldConfig::create([
      'entity_type' => $entityTypeId,
      'bundle' => $bundle,
      'field_name' => $storage->getName(),
    ]);
    $config->save();

    return $config;
  }

}
