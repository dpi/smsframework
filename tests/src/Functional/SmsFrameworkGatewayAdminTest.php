<?php

namespace Drupal\Tests\sms\Functional;

use Drupal\Core\Url;
use Drupal\Component\Utility\Unicode;
use Drupal\sms\Direction;
use Drupal\sms\Entity\SmsGateway;

/**
 * Tests gateway administration user interface.
 *
 * @group SMS Framework
 */
class SmsFrameworkGatewayAdminTest extends SmsFrameworkBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block'];

  /**
   * SMS Gateway entity storage.
   *
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
    $this->createMemoryGateway();

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
    $this->assertRaw(t('No gateways found.'));
  }

  /**
   * Tests setting up the fallback gateway.
   */
  public function testFallbackGateway() {
    $test_gateway = $this->createMemoryGateway(['skip_queue' => TRUE]);

    // Test initial fallback gateway.
    $sms_gateway_fallback = SmsGateway::load($this->config('sms.settings')->get('fallback_gateway'));

    $this->assertEqual($sms_gateway_fallback->id(), 'log', 'Initial fallback gateway is "log".');

    $this->drupalLogin($this->drupalCreateUser(['administer smsframework']));

    // Change fallback gateway.
    $this->drupalPostForm(Url::fromRoute('sms.settings'), [
      'fallback_gateway' => $test_gateway->id(),
    ], 'Save configuration');
    $this->assertResponse(200);

    $sms_gateway_fallback = SmsGateway::load($this->config('sms.settings')->get('fallback_gateway'));
    $this->assertEqual($sms_gateway_fallback->id(), $test_gateway->id(), 'Fallback gateway changed.');
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
    $this->assertNoFieldChecked('edit-skip-queue');
    $this->assertFieldByName('retention_duration_incoming', '0');
    $this->assertFieldByName('retention_duration_outgoing', '0');

    // Memory gateway supports pushed reports, so the URL should display.
    $this->assertFieldByName('delivery_reports[push_path]', $test_gateway->getPushReportPath());

    // Memory gateway has a decoy configuration form.
    $edit = [
      'widget' => $this->randomString(),
      'skip_queue' => '1',
      'retention_duration_incoming' => '3600',
      'retention_duration_outgoing' => '-1',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertUrl(Url::fromRoute('sms.gateway.list'));
    $this->assertResponse(200);
    $this->assertRaw('Gateway saved.');

    // Reload the gateway, check configuration saved to config entity.
    /** @var \Drupal\sms\Entity\SmsGatewayInterface $test_gateway */
    $test_gateway = $this->smsGatewayStorage
      ->load($test_gateway->id());

    // Gateway settings.
    $this->assertEqual(TRUE, $test_gateway->getSkipQueue());
    $this->assertEqual($edit['retention_duration_incoming'], $test_gateway->getRetentionDuration(Direction::INCOMING));
    $this->assertEqual($edit['retention_duration_outgoing'], $test_gateway->getRetentionDuration(Direction::OUTGOING));

    // Plugin form.
    $config = $test_gateway->getPlugin()
      ->getConfiguration();
    $this->assertEqual($edit['widget'], $config['widget'], 'Plugin configuration changed.');
  }

  /**
   * Tests a gateway edit form does not display delivery report URL.
   */
  public function testGatewayEditNoDeliveryUrl() {
    $this->drupalLogin($this->drupalCreateUser(['administer smsframework']));
    $test_gateway = $this->createMemoryGateway(['plugin' => 'capabilities_default']);

    $this->drupalGet(Url::fromRoute('entity.sms_gateway.edit_form', [
      'sms_gateway' => $test_gateway->id(),
    ]));
    $this->assertResponse(200);
    $this->assertRaw('Edit gateway');

    $this->assertNoFieldByName('delivery_reports[push_path]');
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

  /**
   * Tests incoming specific features of gateway edit form.
   */
  public function testIncomingGatewayEdit() {
    $gateway = $this->createMemoryGateway(['plugin' => 'incoming']);
    $this->drupalLogin($this->drupalCreateUser(['administer smsframework']));

    $url = Url::fromRoute('entity.sms_gateway.edit_form')
      ->setRouteParameter('sms_gateway', $gateway->id());
    $this->drupalGet($url);

    // Memory gateway supports incoming messages, so the URL should display.
    $this->assertSession()
      ->fieldValueEquals('incoming_messages[push_path]', $gateway->getPushIncomingPath());

    $incoming_route = '/' . $this->randomMachineName();
    $edit = [
      'incoming_messages[push_path]' => $incoming_route,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Reload the gateway, check properties modified.
    $gateway = SmsGateway::load($gateway->id());
    $this->assertEquals($incoming_route, $gateway->getPushIncomingPath());
  }

  /**
   * Tests a gateway edit form does not contain incoming path fields.
   */
  public function testNoIncomingFields() {
    $this->drupalLogin($this->drupalCreateUser(['administer smsframework']));
    $gateway = $this->createMemoryGateway(['plugin' => 'capabilities_default']);

    $this->drupalGet(Url::fromRoute('entity.sms_gateway.edit_form', [
      'sms_gateway' => $gateway->id(),
    ]));

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Edit gateway');
    $this->assertSession()->fieldNotExists('incoming_messages[push_path]');
    $this->assertSession()->responseContains(t('This gateway does not support receiving messages.'));
  }

}
