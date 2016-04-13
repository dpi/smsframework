<?php

/**
 * @file
 * Contains \Drupal\sms\Tests\SmsFrameworkSettingsTest.
 */

namespace Drupal\sms\Tests;

use Drupal\Core\Url;

/**
 * Tests SMS Framework settings form.
 *
 * @group SMS Framework
 */
class SmsFrameworkSettingsTest extends SmsFrameworkWebTestBase {

  /**
   * Test changing verification path.
   */
  public function testSettingsForm() {
    $account = $this->drupalCreateUser([
      'administer smsframework',
    ]);
    $this->drupalLogin($account);

    // Test invalid path.
    $edit = [
      'default_gateway' => 'log',
      'pages[verify]' => $this->randomMachineName() . '/' . $this->randomMachineName(),
    ];
    $this->drupalPostForm(Url::fromRoute('sms.settings'), $edit, 'Save configuration');
    $this->assertRaw(t("Path must begin with a '/' character."));

    // Test submission
    $edit = [
      'default_gateway' => 'log',
      'pages[verify]' => '/' . $this->randomMachineName(),
    ];
    $this->drupalPostForm(Url::fromRoute('sms.settings'), $edit, 'Save configuration');
    $this->assertRaw(t('SMS settings saved.'));
  }

}
