<?php

namespace Drupal\Tests\sms\Kernel\Migrate;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests Drupal 7 SMS User phone number migrations.
 *
 * Actual tests are in the trait MigratePhoneNumberTestTrait.
 *
 * @group SMS Framework
 *
 * @see \Drupal\Tests\sms\Kernel\Migrate\MigratePhoneNumberTestTrait
 */
class MigrateD7SmsPhoneNumberTest extends MigrateDrupal7TestBase {

  use MigratePhoneNumberTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadFixture(__DIR__ . '/../../../fixtures/migrate/drupal7.php');
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
    // @todo Work out a better fix https://www.drupal.org/project/smsframework/issues/2951758
    if (method_exists($this, 'expectException')) {
      $this->expectException(RequirementsException::class);
      $this->expectExceptionMessageRegExp('/Missing migrations (d7_user|phone_number_settings), (d7_user|phone_number_settings)/');
    }
    else {
      $this->setExpectedExceptionRegExp(RequirementsException::class, '/Missing migrations (d7_user|phone_number_settings), (d7_user|phone_number_settings)/');
    }
    $this->getMigration('d7_sms_number')->checkRequirements();
  }

  /**
   * {@inheritdoc}
   */
  protected function getMigrationsToTest() {
    return [
      'd7_filter_format',
      'd7_user_role',
      'd7_user',
      'phone_number_settings',
      'd7_sms_number',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMigrationsToRollback() {
    return [
      'd7_sms_number',
      'phone_number_settings',
    ];
  }

  /**
   * File path to the DB fixture for sms_user table and records.
   */
  protected function smsUserFixtureFilePath() {
    return __DIR__ . '/../../../fixtures/migrate/sms_user_drupal7.php';
  }

  /**
   * {@inheritdoc}
   */
  protected function confirmationMessageFixturePath() {
    return __DIR__ . '/../../../fixtures/migrate/sms_confirmation_message_d7.php';
  }

}
