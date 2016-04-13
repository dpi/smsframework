<?php

/**
 * @file
 * Contains \Drupal\sms\Tests\SmsFrameworkGatewayAdminTest.
 */

namespace Drupal\sms\Tests;

use Drupal\Core\Url;
use Drupal\Component\Utility\Unicode;

/**
 * Tests phone number administration user interface.
 *
 * @group SMS Framework
 */
class SmsFrameworkGatewayAdminTest extends SmsFrameworkWebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block'];

  /**
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $smsGatewayStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->smsGatewayStorage = \Drupal::entityTypeManager()
      ->getStorage('sms_gateway');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests the Gateway list implementation.
   */
  public function testGatewayList() {
    // Test no access for anonymous.
    $this->drupalGet(Url::fromRoute('sms.gateway.list'));
    $this->assertResponse(403);

    $account = $this->drupalCreateUser(['administer smsframework']);
    $this->drupalLogin($account);

    $this->drupalGet(Url::fromRoute('sms.gateway.list'));
    $this->assertResponse(200);
    $this->assertRaw('<td>Drupal log</td>');
    $this->assertRaw('<td>Memory</td>');

    // Delete all gateways.
    $this->smsGatewayStorage->delete($this->smsGatewayStorage->loadMultiple());
    $this->drupalGet(Url::fromRoute('sms.gateway.list'));
    $this->assertRaw(t('No SMS Gateways found.'));
  }

  /**
   * Tests setting up the default gateway.
   */
  public function testDefaultGateway() {
    // Test initial default gateway.
    $sms_gateway_default = $this->defaultSmsProvider->getDefaultGateway();

    $this->assertEqual($sms_gateway_default->id(), 'log', 'Initial default gateway is "log".');

    $this->drupalLogin($this->drupalCreateUser(['administer smsframework']));

    // Change default gateway.
    $this->drupalPostForm('admin/config/smsframework/settings', [
      'default_gateway' => $this->testGateway->id(),
    ], 'Save configuration');
    $this->assertResponse(200);

    $sms_gateway_default = $this->defaultSmsProvider->getDefaultGateway();
    $this->assertEqual($sms_gateway_default->id(), $this->testGateway->id(), 'Default gateway changed.');
  }

  /**
   * Test adding a gateway.
   */
  public function testGatewayAdd() {
    $account = $this->drupalCreateUser(['administer smsframework']);
    $this->drupalLogin($account);

    $this->drupalGet(Url::fromRoute('entity.sms_gateway.add'));
    $this->assertResponse(200);

    $edit = [
      'label' => $this->randomString(),
      'id' => Unicode::strtolower($this->randomMachineName()),
      'status' => TRUE,
      'plugin_id' => 'memory',
    ];
    $this->drupalPostForm(Url::fromRoute('entity.sms_gateway.add'), $edit, t('Save'));
    $this->assertResponse(200);

    $this->assertUrl(Url::fromRoute('entity.sms_gateway.edit_form', [
      'sms_gateway' => $edit['id'],
    ]));
    $this->assertRaw(t('Gateway created.'));

    $this->drupalGet(Url::fromRoute('sms.gateway.list'));
    $this->assertRaw('<td>' . t('@label', ['@label' => $edit['label']]) . '</td>', 'New gateway appears on list.');
  }

  /**
   * Tests configuring a gateway.
   *
   * Ensures gateway plugin custom configuration form is shown, and new
   * configuration is saved to the config entity.
   */
  public function testGatewayEdit() {
    $this->drupalLogin($this->drupalCreateUser(['administer smsframework']));
    $test_gateway = $this->createMemoryGateway();

    $this->drupalGet(Url::fromRoute('entity.sms_gateway.edit_form', [
      'sms_gateway' => $test_gateway->id(),
    ]));
    $this->assertResponse(200);
    $this->assertFieldByName('widget');

    // Memory gateway has a decoy configuration form.
    $edit = [
      'widget' => $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertUrl(Url::fromRoute('sms.gateway.list'));
    $this->assertResponse(200);
    $this->assertRaw('Gateway saved.');

    // Reload the gateway, check configuration saved to config entity.
    /** @var \Drupal\sms\Entity\SmsGatewayInterface $test_gateway */
    $test_gateway = $this->smsGatewayStorage->load($test_gateway->id());
    $config = $test_gateway->getPlugin()->getConfiguration();
    $this->assertEqual($edit['widget'], $config['widget'], 'Plugin configuration changed.');
  }

  /**
   * Tests deleting a gateway.
   */
  public function testGatewayDelete() {
    $this->drupalLogin($this->drupalCreateUser(['administer smsframework']));
    $test_gateway = $this->createMemoryGateway();
    $this->drupalGet(Url::fromRoute('entity.sms_gateway.edit_form', [
      'sms_gateway' => $test_gateway->id(),
    ]));

    $this->clickLink(t('Delete'));
    $delete_url = Url::fromRoute('entity.sms_gateway.delete_form', [
      'sms_gateway' => $test_gateway->id(),
    ]);
    $this->assertUrl($delete_url);
    $this->assertRaw(t('Are you sure you want to delete SMS gateway %label?', [
      '%label' => $test_gateway->label(),
    ]));
    $this->drupalPostForm($delete_url, [], t('Delete'));

    $this->assertUrl(Url::fromRoute('sms.gateway.list'));
    $this->assertResponse(200);
    $this->assertRaw(t('Gateway %label was deleted.', [
      '%label' => $test_gateway->label(),
    ]));
    $this->assertNoRaw('<td>' . t('@label', ['@label' => $test_gateway->label()]) . '</td>');
  }

}
