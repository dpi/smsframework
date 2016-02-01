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
    $this->testGateway = SmsGateway::create([
      'plugin' => 'memory',
      'id' => Unicode::strtolower($this->randomMachineName(16)),
      'label' => $this->randomString(),
    ]);
    $this->testGateway->enable();
    $this->testGateway->save();
  }

  /**
   * Get all SMS messages sent to 'Memory' gateway.
   *
   * @return \Drupal\sms\Message\SmsMessageInterface[]
   */
  function getTestMessages() {
    return \Drupal::state()->get('sms_test_gateway.memory.send', []);
  }

  /**
   * Get the last SMS message sent to 'Memory' gateway.
   *
   * @return \Drupal\sms\Message\SmsMessageInterface|NULL
   *   The last SMS message, or FALSE if no messages have been sent.
   */
  public function getLastTestMessage() {
    $sms_messages = \Drupal::state()->get('sms_test_gateway.memory.send', []);
    return end($sms_messages);
  }

  /**
   * Resets SMS messages stored in memory by 'Memory' gateway.
   */
  public function resetTestMessages() {
    \Drupal::state()->set('sms_test_gateway.memory.send', []);
  }

  /**
   * Utility to create phone number settings
   *
   * Creates new field storage and field configs.
   *
   * @return \Drupal\sms\Entity\PhoneNumberSettingsInterface
   *   A phone number settings entity.
   */
  protected function createPhoneNumberSettings() {
    $entity_type_manager = \Drupal::entityTypeManager();

    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
    $field_storage = $entity_type_manager->getStorage('field_storage_config')
      ->create([
        'entity_type' => 'entity_test',
        'field_name' => Unicode::strtolower($this->randomMachineName()),
        'type' => 'telephone',
      ]);
    $field_storage
//      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setCardinality(1)
      ->save();

    $entity_type_manager->getStorage('field_config')
      ->create([
        'entity_type' => 'entity_test',
        'bundle' => 'entity_test',
        'field_name' => $field_storage->getName(),
      ])->save();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity_form_display */
    $entity_form_display = $entity_type_manager
      ->getStorage('entity_form_display')
      ->load('entity_test.entity_test.default');
    $entity_form_display
      ->setComponent($field_storage->getName(), ['type' => 'sms_telephone'])
      ->save();

    /** @var \Drupal\sms\Entity\PhoneNumberSettingsInterface $phone_number_settings */
    $phone_number_settings = $entity_type_manager
      ->getStorage('phone_number_settings')
      ->create();

    $phone_number_settings
      ->setFieldName('phone_number', $field_storage->getName())
      ->setPhoneNumberEntityTypeId('entity_test')
      ->setPhoneNumberBundle('entity_test')
      ->setVerificationLifetime(3601)
      ->setVerificationMessage('Verification code is [sms:verification-code]')
      ->setVerificationPhoneNumberPurge(TRUE)
      ->save();

    return $phone_number_settings;
  }

  protected function createEntityWithPhoneNumber(PhoneNumberSettingsInterface $phone_number_settings, $phone_numbers = []) {
    $field_name = $phone_number_settings->getFieldName('phone_number');
    $entity_type_manager = \Drupal::entityTypeManager();
    $test_entity = $entity_type_manager->getStorage('entity_test')
      ->create([
        'name' => $this->randomMachineName(),
      ]);

    foreach ($phone_numbers as $phone_number) {
      $test_entity->{$field_name}[] = $phone_number;
    }

    $test_entity->save();
    return $test_entity;
  }

  /**
   * xx
   *
   * @return \Drupal\sms\Entity\PhoneNumberVerificationInterface|NULL
   */
  protected function getVerificationCodeLast() {
    $verification_storage = \Drupal::entityTypeManager()
      ->getStorage('sms_phone_number_verification');

    $verification_ids = $verification_storage->getQuery()
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();
    $verifications = $verification_storage->loadMultiple($verification_ids);

    return reset($verifications);
  }

}
