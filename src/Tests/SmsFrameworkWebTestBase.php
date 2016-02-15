<?php

/**
 * @file
 * Contains \Drupal\sms\Tests\SmsFrameworkWebTestBase.
 */

namespace Drupal\sms\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\sms\Entity\SmsGateway;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\sms\Entity\PhoneNumberSettingsInterface;

/**
 * Provides commonly used functionality for tests.
 */
abstract class SmsFrameworkWebTestBase extends WebTestBase {

  use SmsFrameworkTestTrait;

  public static $modules = ['sms', 'sms_test_gateway', 'telephone', 'dynamic_entity_reference'];

  /**
   * The gateway manager.
   *
   * @var \Drupal\sms\Plugin\SmsGatewayPluginManagerInterface
   */
  protected $gatewayManager;

  /**
   * The default SMS provider service.
   *
   * @var \Drupal\sms\Provider\DefaultSmsProvider
   */
  protected $defaultSmsProvider;

  /**
   * 'Memory' test gateway instance.
   *
   * @var \Drupal\sms\Entity\SmsGatewayInterface
   */
  protected $testGateway;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->gatewayManager = $this->container->get('plugin.manager.sms_gateway');
    $this->defaultSmsProvider = $this->container->get('sms_provider.default');

    // Add an instance of test gateway.
    $this->testGateway = $this->createMemoryGateway();
  }

  /**
   * Utility to create phone number settings
   *
   * Creates new field storage and field configs.
   *
   * @return \Drupal\sms\Entity\PhoneNumberSettingsInterface
   *   A phone number settings entity.
   */
  protected function createPhoneNumberSettings($entity_type_id, $bundle) {
    $entity_type_manager = \Drupal::entityTypeManager();

    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
    $field_storage = $entity_type_manager->getStorage('field_storage_config')
      ->create([
        'entity_type' => $entity_type_id,
        'field_name' => Unicode::strtolower($this->randomMachineName()),
        'type' => 'telephone',
      ]);
    $field_storage
//      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setCardinality(1)
      ->save();

    $entity_type_manager->getStorage('field_config')
      ->create([
        'entity_type' => $entity_type_id,
        'bundle' => $bundle,
        'field_name' => $field_storage->getName(),
      ])->save();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity_form_display */
    $entity_form_display = $entity_type_manager
      ->getStorage('entity_form_display')
      ->load($entity_type_id . '.' . $bundle . '.default');
    $entity_form_display
      ->setComponent($field_storage->getName(), ['type' => 'sms_telephone'])
      ->save();

    /** @var \Drupal\sms\Entity\PhoneNumberSettingsInterface $phone_number_settings */
    $phone_number_settings = $entity_type_manager
      ->getStorage('phone_number_settings')
      ->create();

    $phone_number_settings
      ->setFieldName('phone_number', $field_storage->getName())
      ->setPhoneNumberEntityTypeId($entity_type_id)
      ->setPhoneNumberBundle($bundle)
      ->setVerificationCodeLifetime(3601)
      ->setVerificationMessage('Verification code is [sms:verification-code]')
      ->setPurgeVerificationPhoneNumber(TRUE)
      ->save();

    return $phone_number_settings;
  }

}
