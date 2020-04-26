<?php

namespace Drupal\Tests\sms_user\Functional;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Tests\sms\Functional\SmsFrameworkBrowserTestBase;
use Drupal\Core\Url;

/**
 * Tests dynamically created SMS User menu links.
 *
 * @group SMS User
 */
class SmsFrameworkUserMenuLinksTest extends SmsFrameworkBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['sms_user'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

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
    $entityDisplayRepo = \Drupal::service('entity_display.repository');
    assert($entityDisplayRepo instanceof EntityDisplayRepositoryInterface);
    $entityDisplayRepo->getFormDisplay('user', 'user', 'default')->save();
    $this->createPhoneNumberSettings('user', 'user');
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
