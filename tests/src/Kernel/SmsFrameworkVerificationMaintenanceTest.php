<?php

/**
 * @file
 * Contains \Drupal\Tests\sms\Kernel\SmsFrameworkVerificationMaintenanceTest.
 */

namespace Drupal\Tests\sms\Kernel;

use Drupal\sms\Entity\PhoneNumberSettings;
use Drupal\sms\Entity\PhoneNumberVerificationInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests verification maintenance executed during cron.
 *
 * @group SMS Framework
 */
class SmsFrameworkVerificationMaintenanceTest extends SmsFrameworkKernelBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['sms', 'entity_test', 'user', 'field', 'telephone', 'dynamic_entity_reference'];

  /**
   * @var \Drupal\sms\Entity\PhoneNumberSettingsInterface
   */
  protected $phoneNumberSettings;

  /**
   * @var \Drupal\field\FieldStorageConfigInterface
   */
  protected $phoneField;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('sms_phone_number_verification');
    $this->installConfig('sms');

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
      ->setVerificationPhoneNumberPurge(FALSE)
      ->setVerificationLifetime(3600)
      ->save();

    $this->testEntity = $this->createEntityWithPhoneNumber($this->phoneNumberSettings, ['+123123123']);
  }

  /**
   * Test unverified verification which have not expired.
   */
  public function testVerificationUnverifiedNotExpired() {
    $this->getVerificationCodeLast()
      ->setStatus(FALSE)
      ->save();
    $this->container->get('cron')->run();
    $this->assertTrue($this->getVerificationCodeLast() instanceof PhoneNumberVerificationInterface);
  }

  /**
   * Test unverified verification which have expired are deleted.
   */
  public function testVerificationUnverifiedExpired() {
    $this->getVerificationCodeLast()
      ->setStatus(FALSE)
      ->set('created', 0)
      ->save();
    $this->container->get('cron')->run();
    $this->assertFalse($this->getVerificationCodeLast());
  }

  /**
   * Test unverified verification which have expired do not purge field data.
   */
  public function testVerificationUnverifiedExpiredNoPurgeFieldData() {
    $this->getVerificationCodeLast()
      ->setStatus(FALSE)
      ->set('created', 0)
      ->save();
    $this->container->get('cron')->run();
    $this->testEntity = EntityTest::load($this->testEntity->id());
    $this->assertNotEmpty($this->testEntity->{$this->phoneField->getName()});
  }

  /**
   * Test unverified verification which have expired purge field data.
   */
  public function testVerificationUnverifiedExpiredPurgeFieldData() {
    $this->phoneNumberSettings
      ->setVerificationPhoneNumberPurge(TRUE)
      ->save();
    $this->getVerificationCodeLast()
      ->setStatus(FALSE)
      ->set('created', 0)
      ->save();
    $this->container->get('cron')->run();
    $this->testEntity = EntityTest::load($this->testEntity->id());
    $this->assertEmpty($this->testEntity->{$this->phoneField->getName()});
  }

  /**
   * Test verified verification.
   */
  public function testVerificationVerifiedExpired() {
    $this->getVerificationCodeLast()
      ->setStatus(TRUE)
      ->set('created', 0)
      ->save();
    $this->container->get('cron')->run();
    $this->assertTrue($this->getVerificationCodeLast() instanceof PhoneNumberVerificationInterface);
  }

}
