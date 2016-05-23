<?php

namespace Drupal\sms_user\Tests;

use Drupal\sms\Tests\SmsFrameworkWebTestBase;
use Drupal\Core\Url;

/**
 * Tests dynamically created SMS User menu links
 *
 * @group SMS User
 */
class SmsFrameworkUserMenuLinksTest extends SmsFrameworkWebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['sms_user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $user = $this->drupalCreateUser([
      'access administration pages',
      'administer smsframework',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests dynamic menu links are found.
   */
  public function testDynamicMenuLinks() {
    entity_get_form_display('user', 'user', 'default')->save();
    $this->createPhoneNumberSettings('user', 'user');
    $this->rebuildAll();
    $this->drupalGet(Url::fromRoute('user.admin_index'));
    $this->assertLink('User phone number');
  }

  /**
   * Tests no dynamic menu links are found.
   */
  public function testNoDynamicMenuLinks() {
    $this->drupalGet(Url::fromRoute('user.admin_index'));
    $this->assertNoLink('User phone number');
  }

}
