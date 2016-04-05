<?php

/**
 * @file
 * Contains \Drupal\Tests\sms\Unit\Message\SmsMessageTest
 */

namespace Drupal\Tests\sms\Unit\Message;

use Drupal\Component\Uuid\Php;
use Drupal\sms\Message\SmsMessage;
use Drupal\Tests\UnitTestCase;
use Drupal\sms\Tests\SmsFrameworkMessageTestTrait;

/**
 * Unit Tests for SmsMessage.
 *
 * @group SmsFramework
 * @coversDefaultClass \Drupal\sms\Message\SmsMessage
 */
class SmsMessageTest extends UnitTestCase {

  use SmsFrameworkMessageTestTrait;

  /**
   * Create a SMS message object for testing.
   *
   * @return \Drupal\Tests\sms\Unit\Message\TestSmsMessage
   */
  private function createSmsMessage() {
    return new TestSmsMessage();
  }

}

/**
 * Mock class for testing.
 */
class TestSmsMessage extends SmsMessage {

  /**
   * {@inheritdoc}
   */
  protected function uuidGenerator() {
    return new Php();
  }

}
