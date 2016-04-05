<?php

/**
 * @file
 * Contains \Drupal\Tests\sms\Kernel\SmsFrameworkMessageEntityTest.
 */

namespace Drupal\Tests\sms\Kernel;

use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Tests\SmsFrameworkMessageTestTrait;
use Drupal\user\Entity\User;

/**
 * Tests SMS message entity.
 *
 * @group SMS Framework
 * @coversDefaultClass \Drupal\sms\Entity\SmsMessage
 */
class SmsFrameworkMessageEntityTest extends SmsFrameworkKernelBase {

  use SmsFrameworkMessageTestTrait {
    // Remove 'test' prefix so it will not be run by test runner, rename so we
    // can override.
    testUid as originalUid;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['sms', 'telephone', 'dynamic_entity_reference', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('sms');
    $this->installEntitySchema('user');
  }

  /**
   * Create a SMS message object for testing.
   *
   * @return \Drupal\sms\Entity\SmsMessageInterface
   */
  private function createSmsMessage() {
    return SmsMessage::create();
  }

  /**
   * @inheritdoc
   */
  public function testUid() {
    // User must exist or setUid will throw an exception.
    User::create(['uid' => 22, 'name' => 'user'])
      ->save();
    $this->originalUid();
  }

}
