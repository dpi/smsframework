<?php

namespace Drupal\Tests\sms\Functional;

use Drupal\Core\Url;
use Drupal\sms\Entity\SmsGateway;

/**
 * Tests gateway administration user interface.
 *
 * \Drupal\sms\Tests\SmsFrameworkGatewayAdminTest to be migrated to this class.
 *
 * @group SMS Framework
 */
class SmsFrameworkGatewayAdminTest extends SmsFrameworkBrowserTestBase {

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
