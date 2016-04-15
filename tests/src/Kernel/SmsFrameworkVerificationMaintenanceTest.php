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
  public static $modules = ['sms', 'sms_test_gateway', 'entity_test', 'user', 'field', 'telephone', 'dynamic_entity_reference'];

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
      ->setPurgeVerificationPhoneNumber(FALSE)
      ->setVerificationCodeLifetime(3600)
      ->setVerificationMessage($this->randomString())
      ->save();

    /** @var \Drupal\sms\Provider\DefaultSmsProvider $provider */
    $provider = \Drupal::service('sms_provider');
    $provider->setDefaultGateway($this->createMemoryGateway(['skip_queue' => TRUE]));

    $this->testEntity = $this->createEntityWithPhoneNumber($this->phoneNumberSettings, ['+123123123']);
  }

  /**
   * Test unverified verification which have not expired.
   */
  public function testVerificationUnverifiedNotExpired() {
    $this->getLastVerification()
      ->setStatus(FALSE)
      ->save();
    $this->container->get('cron')->run();
    $this->assertTrue($this->getLastVerification() instanceof PhoneNumberVerificationInterface);
  }

  /**
   * Test unverified verification which have expired are deleted.
   */
  public function testVerificationUnverifiedExpired() {
    $this->getLastVerification()
      ->setStatus(FALSE)
      ->set('created', 0)
      ->save();
    $this->container->get('cron')->run();
    $this->assertFalse($this->getLastVerification());
  }

  /**
   * Test unverified verification which have expired do not purge field data.
   */
  public function testVerificationUnverifiedExpiredNoPurgeFieldData() {
    $this->getLastVerification()
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
      ->setPurgeVerificationPhoneNumber(TRUE)
      ->save();
    $this->getLastVerification()
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
    $this->getLastVerification()
      ->setStatus(TRUE)
      ->set('created', 0)
      ->save();
    $this->container->get('cron')->run();
    $this->assertTrue($this->getLastVerification() instanceof PhoneNumberVerificationInterface);
  }

}
