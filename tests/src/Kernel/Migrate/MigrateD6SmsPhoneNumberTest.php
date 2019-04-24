<?php

namespace Drupal\Tests\sms\Kernel\Migrate;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests Drupal 6 SMS User phone number migrations.
 *
 * Actual tests are in the trait MigratePhoneNumberTestTrait.
 *
 * @group SMS Framework
 *
 * @see \Drupal\Tests\sms\Kernel\Migrate\MigratePhoneNumberTestTrait
 */
class MigrateD6SmsPhoneNumberTest extends MigrateDrupal6TestBase {

  use MigratePhoneNumberTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadFixture(__DIR__ . '/../../../fixtures/migrate/drupal6.php');
  }

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'sms',
    'telephone',
    'dynamic_entity_reference',
    'filter',
  ];

  /**
   * Tests that the requirements for the d7_sms_number migration are enforced.
   */
  public function testMigrationRequirements() {
    $this->setExpectedException(RequirementsException::class, 'Missing migrations d6_user, phone_number_settings.');
    $this->getMigration('d6_sms_number')->checkRequirements();
  }

  /**
   * {@inheritdoc}
   */
  protected function getMigrationsToTest() {
    return [
      'd6_filter_format',
      'd6_user_role',
      'd6_user',
      'phone_number_settings',
      'd6_sms_number',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMigrationsToRollback() {
    return [
      'd6_sms_number',
      'phone_number_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function smsUserFixtureFilePath() {
    return __DIR__ . '/../../../fixtures/migrate/sms_user_drupal6.php';
  }

  /**
   * {@inheritdoc}
   */
  protected function confirmationMessageFixturePath() {
    return __DIR__ . '/../../../fixtures/migrate/sms_confirmation_message_d6.php';
  }

}
