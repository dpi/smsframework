<?php

/**
 * @file
 * Contains tests for the functions in sms.module and core sms framework.
 */

namespace Drupal\sms_valid\Tests;

use \Drupal\simpletest\WebTestBase;
use Drupal\sms_valid\Entity\Ruleset;

/**
 * Tests for the SMS Valid module.
 *
 * @group SMS Framework
 */
class SmsValidWebTest extends WebTestBase {
  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  public static $modules = ['sms', 'sms_valid'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(array('administer smsframework'));
  }

  /**
   * Tests the sms_valid_validate() function.
   */
  public function testSmsValidValidate() {
    // Test that the default rulesets are added already.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/smsframework/validation/rulesets');
    $this->assertText('New Zealand', 'Default ruleset found in list view.');

    $test_numbers = array(
      '1234567890' => false,
      '123458767890' => false,
      '64219427-9238' => true,
      '6425=-,x2-4n292' => false,
      '6429;ajklf a/s,MFA' => true,
      '] W[OPQIRW' => false,
      '6429996789065' => true,
      '6428156789098765' => true,
    );

    // Test direct validation.
    $options = array('test' => true);
    foreach ($test_numbers as $number => $valid) {
      $result = sms_valid_validate($number, $options);
      $this->assertEqual($valid, empty($result['errors']), 'Direct test: Number validation ok for ' . $number);
    }

    // Test through hook_sms_validate_number().
    foreach ($test_numbers as $number => $valid) {
      $errors = sms_validate_number($number, $options);
      $this->assertEqual($valid, empty($errors), 'Hook test: Number validation ok for ' . $number);
    }
  }

  /**
   * Tests the creation, update and deletion of sms_valid rulesets.
   */
  public function testCrudSmsValidRulesets() {
    $this->drupalLogin($this->adminUser);

    // Create a new ruleset.
    $edit = array(
      'prefix' => (string) rand(1, 999),
      'name' => $this->randomMachineName(15),
      'iso2' => 'XX',
      'out' => true,
      'in' => true,
      'rules' => "80+\n90-\n70\n65+\n",
    );
    $this->drupalPostForm('admin/config/smsframework/validation/ruleset', $edit, t('Save Ruleset'));

    // Confirm that ruleset was correctly saved.
    $ruleset = Ruleset::load($edit['prefix'])->toArray();
    unset($ruleset['uuid']);
    $original = Ruleset::create(array(
        'rules' => sms_valid_text_to_rules($edit['rules']),
        'dirs_enabled' => sms_dir($edit['out'], $edit['in']),
      ) + $edit)->toArray();
    unset($original['in'], $original['out'], $original['uuid']);
    $this->assertEqual($original, $ruleset, 'Ruleset ' . $edit['prefix'] . ' correctly saved.');

    // Update ruleset.
    $edit['in'] = false;
    $edit['out'] = false;
    $this->drupalPostForm('admin/config/smsframework/validation/ruleset/' . $edit['prefix'], $edit, t('Save Ruleset'));
    $ruleset = Ruleset::load($edit['prefix']);
    $this->assertEqual('0', $ruleset->dirs_enabled, 'Ruleset ' . $edit['prefix'] . ' updated');

    // Change direction settings from list page.
    $direction_change = array(
      "rulesets[{$edit['prefix']}][in]" => true,
      "rulesets[{$edit['prefix']}][out]" => true,
    );
    $this->drupalPostForm('admin/config/smsframework/validation/rulesets', $direction_change, t('Save Changes'));
    $ruleset = Ruleset::load($edit['prefix']);
    $this->assertEqual($ruleset->dirs_enabled, SMS_DIR_ALL, 'Directionality for ' . $edit['prefix'] . ' changed from list page');

    // Add another test ruleset and then check the ruleset load button.
    $edit1 = array(
        'prefix' => (string) rand(1, 999),
        'name' => $this->randomMachineName(15)
      ) + $edit;
    $this->drupalPostForm('admin/config/smsframework/validation/ruleset', $edit1, t('Save Ruleset'));
    $this->drupalPostForm('admin/config/smsframework/validation/ruleset', array('refresh[refresh_row][select_prefix]' => $edit1['prefix']), t('Refresh Editor (below)'));
    $this->assertFieldByXPath('//input[@name="prefix"]', $edit1['prefix'], 'Refresh button click results in reload of ruleset ' . $edit1['prefix']);

    // Delete ruleset.
    $delete = ["rulesets[{$edit['prefix']}][delete]" => true];
    $this->drupalPostForm('admin/config/smsframework/validation/rulesets', $delete, t('Save Changes'));
    $this->assertFalse(Ruleset::load($edit['prefix']), 'Ruleset ' . $edit['prefix'] . ' deleted');

  }

  /**
   * Tests the application of the sms_valid settings form and settings.
   */
  public function testSmsValidSettingsForm() {
    // Create two rulesets for test.
    $ruleset = $this->randomRuleset();
    $ruleset->prefix = '991';
    $ruleset->rules = sms_valid_text_to_rules("23- # Explicit deny 23");
    $ruleset->save();

    $ruleset = $this->randomRuleset();
    $ruleset->prefix = '234';
    $ruleset->rules = sms_valid_text_to_rules("56+ # Explicit allow 56");
    $ruleset->save();

    // Update settings.
    $edit = array(
      'mode' => 1,
      'global_ruleset' => '64',
      'local_number_prefix' => '0',
      'local_number_ruleset' => '234',
      'last_resort_enabled' => true,
      'last_resort_ruleset' => '991',
    );
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('admin/config/smsframework/validation', $edit, t('Save settings'));

    // Confirm settings were updated correctly.
    $config = $this->config('sms_valid.settings');
    $this->assertTrue($config->get('use_rulesets'), 'Use rulesets correctly set.');
    $this->assertFalse($config->get('use_global_ruleset'), 'Use rulesets correctly set.');
    $this->assertEqual('64', $config->get('global_ruleset'), 'Global ruleset correctly set.');
    $this->assertEqual('0', $config->get('local_number_prefix'), 'Local number prefix correctly set.');
    $this->assertEqual('234', $config->get('local_number_ruleset'), 'Local number ruleset correctly set.');
    $this->assertTrue($config->get('last_resort_enabled'), 'Last resort option correctly set.');
    $this->assertEqual('991', $config->get('last_resort_ruleset'), 'Last resort ruleset correctly set.');

    // Use specific ruleset.
    $result = sms_valid_validate('2345678901234', ['prefix' => '991']);
    $this->assertFalse($result['pass'], 'Specific ruleset caused fail.');
    $result = sms_valid_validate('2345678901234', ['prefix' => '234']);
    $this->assertTrue($result['pass'], 'Specific ruleset caused pass.');

    // Use global ruleset.
    $this->config('sms_valid.settings')->set('use_global_ruleset', true)
      ->set('global_ruleset', '234')
      ->save();
    $result = sms_valid_validate('2345678901234');
    $this->assertFalse($result['pass'], 'Global ruleset "234" applied to valid number');
    $result = sms_valid_validate('9915678901234');
    $this->assertFalse($result['pass'], 'Global ruleset "234" applied to invalid number');

    // Local number validation.
    $ruleset = $this->randomRuleset();
    $ruleset->prefix = '419';
    $ruleset->rules = sms_valid_text_to_rules("80- # Explicit deny 80\n90+ # Explicit allow 90");
    $ruleset->save();
    $this->config('sms_valid.settings')->set('local_number_prefix',  '0')
      ->set('local_number_ruleset', '419')
      ->set('use_global_ruleset', false)
      ->save();

    $result = sms_valid_validate('08078901234');
    $this->assertFalse($result['pass'], 'Local number validation failed as expected for 08078901234');
    $result = sms_valid_validate('09078901234');
    $this->assertTrue($result['pass'], 'Local number validation passed as expected for 09078901234');

    // Last resort ruleset.
    $ruleset = Ruleset::load('991');
    $ruleset->rules = sms_valid_text_to_rules("2- # Explicit deny (2...)\n1+ # Explicit allow (1...)");
    $ruleset->save();
    $this->config('sms_valid.settings')->set('use_global_ruleset',  false)
      ->set('last_resort_enabled',  true)
      ->set('last_resort_ruleset',  '991')
      ->save();
    $result = sms_valid_validate('2189751234');
    $this->assertFalse($result['pass'], 'Fallback rule validation failed as expected for 2189751234');
    $result = sms_valid_validate('1189751234');
    $this->assertTrue($result['pass'], 'Fallback rule validation passed as expected for 1189751234');
  }

  /**
   * Tests the internal sms_valid functions.
   */
  public function testSmsValidFunctions() {
    // Clear all rulesets.
    $count = count(sms_valid_get_all_rulesets());

    $ruleset1 = $this->randomRuleset();
    $ruleset1->iso2 = 'XX';
    $ruleset1->rules = sms_valid_text_to_rules("80- # Test 1\n90+ # Test 2");
    $ruleset1->save();

    $ruleset2 = $this->randomRuleset();
    $ruleset2->iso2 = 'XX';
    $ruleset2->rules = sms_valid_text_to_rules("81- # Test 1\n91+ # Test 2");
    $ruleset2->save();

    $new_rulesets = sms_valid_get_all_rulesets();
    $this->assertEqual(2, count($new_rulesets) - $count, 'All new rulesets found.');
    $this->assertEqual($ruleset2, $new_rulesets[$ruleset2->prefix], 'sms_valid_get_all_rulesets() functional.');
    $this->assertEqual(Ruleset::load($ruleset1->prefix), $ruleset1, 'Ruleset::load() functional.');

    $rules = sms_valid_rules_to_text($ruleset2->rules);
    $this->assertEqual($rules, "81-    # Test 1\n91+    # Test 2", 'Rules converted to text.');
    $rules = sms_valid_text_to_rules($rules);
    $this->assertEqual($rules, $ruleset2->rules, 'Rules converted from text.');

    $this->assertEqual($ruleset2, sms_valid_get_ruleset_for_number($ruleset2->prefix . '98765432'), 'Found right ruleset for ' . $ruleset2->prefix . '98765432');
    // @todo This test may fail at random times.
    $this->assertFalse(sms_valid_get_ruleset_for_number('876298765432'), 'No ruleset for 876298765432');

    // php array comparison needs sorting.
    $expected = array($ruleset1->prefix, $ruleset2->prefix);
    $actual = sms_valid_get_prefixes_for_iso2('XX');
    sort($expected); sort($actual);
    $this->assertEqual($expected, $actual, 'sms_valid_get_prefixes_for_iso2() functional.');

    // Set status.
    sms_valid_ruleset_set_status($ruleset1->prefix, SMS_DIR_ALL);
    $ruleset = Ruleset::load($ruleset1->prefix);
    $this->assertEqual(SMS_DIR_ALL, $ruleset->dirs_enabled, 'sms_valid_ruleset_set_status() functional.');

    // Local number check.
    $this->config('sms_valid.settings')->set('local_number_prefix',  '0')->save();
    $this->assertTrue(sms_valid_is_local_number('08032149857'), 'Local number check passes.');
    $this->assertFalse(sms_valid_is_local_number('8032149857'), 'Local number check passes.');
  }

  /**
   * Creates a random ruleset.
   *
   * @return \Drupal\sms_valid\Entity\Ruleset
   */
  protected function randomRuleset() {
    $ruleset = array(
      'prefix' => (string) rand(1, 999),
      'name' => $this->randomMachineName(),
      'dirs_enabled' => 4,
      'iso2' => strtoupper($this->randomMachineName(1) . $this->randomMachineName(1)),
    );
    // Populate random rules.
    for ($i = 0; $i < rand(0, 10); $i++) {
      $ruleset['rules'][rand(1, 99)] = array(
        'allow' => (rand(1,10) > 5),
        'comment' => $this->randomString(),
      );
    }
    return Ruleset::create($ruleset);
  }

}
