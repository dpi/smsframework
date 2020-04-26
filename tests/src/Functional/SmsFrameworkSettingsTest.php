<?php

namespace Drupal\Tests\sms\Functional;

use Drupal\Core\Url;

/**
 * Tests SMS Framework settings form.
 *
 * @group SMS Framework
 */
class SmsFrameworkSettingsTest extends SmsFrameworkBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $account = $this->drupalCreateUser([
      'administer smsframework',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Test setting form without gateway.
   */
  public function testSettingsForm() {
    $edit['fallback_gateway'] = '';
    $edit['pages[verify]'] = '/' . $this->randomMachineName();
    $this->drupalPostForm(Url::fromRoute('sms.settings'), $edit, 'Save configuration');
    $this->assertRaw(t('SMS settings saved.'));
  }

  /**
   * Test setting gateway.
   */
  public function testGatewaySet() {
    $gateway = $this->createMemoryGateway();
    $edit['fallback_gateway'] = $gateway->id();
    $edit['pages[verify]'] = '/' . $this->randomMachineName();
    $this->drupalPostForm(Url::fromRoute('sms.settings'), $edit, 'Save configuration');
    $this->assertRaw(t('SMS settings saved.'));
  }

  /**
   * Test changing verification path.
   */
  public function testVerificationPathInvalid() {
    // Test invalid path.
    $edit['pages[verify]'] = $this->randomMachineName() . '/' . $this->randomMachineName();
    $this->drupalPostForm(Url::fromRoute('sms.settings'), $edit, 'Save configuration');
    $this->assertRaw(t("Path must begin with a '/' character."));
  }

}
