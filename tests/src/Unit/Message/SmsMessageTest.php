<?php

/**
 * @file
 * Contains \Drupal\Tests\sms\Unit\Message\SmsMessageTest
 */

namespace Drupal\Tests\sms\Unit\Message;

use Drupal\Component\Uuid\Php;
use Drupal\sms\Message\SmsMessage;
use Drupal\Tests\UnitTestCase;

/**
 * Unit Tests for SmsMessage.
 *
 * @group SmsFramework
 * @coversDefaultClass \Drupal\sms\Message\SmsMessage
 * @covers ::getUuid()
 * @covers ::getRecipients()
 */
class SmsMessageTest extends UnitTestCase {

  /**
   * Tests UUIDs and recipients for sms messages.
   */
  public function testUuidAndRecipients() {
    $sms1 = new TestSmsMessage('sender1', ['1234567890'], 'test message one', [], 1);
    $sms2 = new TestSmsMessage('sender2', ['1234567890', '9087654321'], 'test message two', [], 1);

    // Test that UUIDs are different.
    $this->assertNotEquals($sms1->getUuid(), $sms2->getUuid());

    // Test that getRecipients return arrays.
    $this->assertEquals($sms1->getRecipients(), ['1234567890']);
    $this->assertEquals($sms2->getRecipients(), ['1234567890', '9087654321']);
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
