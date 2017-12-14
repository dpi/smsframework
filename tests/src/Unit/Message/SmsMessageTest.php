<?php

namespace Drupal\Tests\sms\Unit\Message;

use Drupal\Component\Uuid\Php;
use Drupal\sms\Message\SmsMessage;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\sms\Functional\SmsFrameworkMessageTestTrait;

/**
 * Unit Tests for SmsMessage.
 *
 * @group SMS Framework
 * @coversDefaultClass \Drupal\sms\Message\SmsMessage
 */
class SmsMessageTest extends UnitTestCase {

  use SmsFrameworkMessageTestTrait;

  /**
   * Create a SMS message object for testing.
   *
   * @return \Drupal\sms\Message\SmsMessageInterface
   *   A SMS message object for testing.
   */
  protected function createSmsMessage() {
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
