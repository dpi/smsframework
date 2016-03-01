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
 */
class SmsMessageTest extends UnitTestCase {

  /**
   * Tests recipients for SMS messages.
   *
   * @covers ::getSender
   * @covers ::setSender
   */
  public function testSender() {
    $sender = $this->randomMachineName();
    $sms_message1 = new TestSmsMessage();
    $sms_message1->setSender($sender);
    $this->assertEquals($sender, $sms_message1->getSender());
  }

  /**
   * Tests recipients for SMS messages.
   *
   * @covers ::getMessage
   * @covers ::setMessage
   */
  public function testMessage() {
    $message = $this->randomMachineName();
    $sms_message1 = new TestSmsMessage();
    $sms_message1->setMessage($message);
    $this->assertEquals($message, $sms_message1->getMessage());
  }

  /**
   * Tests recipients for SMS messages.
   *
   * @covers ::getRecipients
   */
  public function testRecipients() {
    $sms_message1 = new TestSmsMessage('sender1', ['1234567890']);
    $sms_message2 = new TestSmsMessage('sender2', ['1234567890', '9087654321']);

    // Test that getRecipients return arrays.
    $this->assertEquals($sms_message1->getRecipients(), ['1234567890']);
    $this->assertEquals($sms_message2->getRecipients(), ['1234567890', '9087654321']);
  }

  /**
   * Tests adding recipients to SMS messages.
   *
   * @covers ::addRecipient
   */
  public function testRecipientAdd() {
    $recipient1 = '123123123';
    $recipient2 = '456456456';
    $sms_message1 = new TestSmsMessage();
    $sms_message1
      ->addRecipient($recipient1)
      ->addRecipient($recipient2);
    $this->assertEquals([$recipient1, $recipient2], $sms_message1->getRecipients());

    // Check duplicate recipients are not added.
    $sms_message2 = new TestSmsMessage();
    $sms_message2
      ->addRecipients([$recipient1, $recipient1, $recipient1, $recipient2]);
    $this->assertEquals([$recipient1, $recipient2], $sms_message2->getRecipients());
  }

  /**
   * Tests adding multiple recipients to SMS messages.
   *
   * @covers ::addRecipients
   */
  public function testRecipientsAdd() {
    $recipient1 = '123123123';
    $recipient2 = '456456456';
    $sms_message2 = new TestSmsMessage();
    $sms_message2
      ->addRecipients([$recipient1, $recipient2]);
    $this->assertEquals([$recipient1, $recipient2], $sms_message2->getRecipients());
  }

  /**
   * Tests removing recipients from SMS messages.
   *
   * @covers ::removeRecipient
   */
  public function testRecipientRemove() {
    $recipient1 = '123123123';
    $recipient2 = '456456456';
    $sms_message1 = new TestSmsMessage();
    $sms_message1
      ->addRecipient($recipient1)
      ->addRecipient($recipient2);
    $sms_message1->removeRecipient($recipient1);
    $this->assertEquals([$recipient2], $sms_message1->getRecipients());
  }

  /**
   * Tests recipients for SMS messages.
   *
   * @covers ::setOption
   * @covers ::getOptions
   */
  public function testOptionsSet() {
    $options = ['foo' => $this->randomMachineName()];
    $sms_message1 = new TestSmsMessage();
    $sms_message1->setOption('foo', $options['foo']);
    $this->assertEquals($options, $sms_message1->getOptions());
  }

  /**
   * Tests recipients for SMS messages.
   *
   * @covers ::removeOption
   */
  public function testOptionsRemove() {
    $options = ['foo' => $this->randomMachineName(), 'bar' => $this->randomMachineName()];
    $sms_message1 = new TestSmsMessage();
    $sms_message1->setOption('foo', $options['foo']);
    $sms_message1->setOption('bar', $options['bar']);
    $sms_message1->removeOption('foo');
    unset($options['foo']);
    $this->assertEquals($options, $sms_message1->getOptions());
  }

  /**
   * Tests adding recipients to SMS messages.
   *
   * @covers ::getUid
   * @covers ::setUid
   */
  public function testUid() {
    $sms_message1 = new TestSmsMessage();

    // Default value.
    $this->assertEquals($sms_message1->getUid(), NULL);

    // Set value.
    $sms_message2 = new TestSmsMessage();
    $sms_message2->setUid(22);
    $this->assertEquals(22, $sms_message2->getUid());
  }

  /**
   * Tests adding recipients to SMS messages.
   *
   * @covers ::setAutomated
   * @covers ::isAutomated
   */
  public function testAutomated() {
    $sms_message1 = new TestSmsMessage();

    // Default
    $this->assertEquals(TRUE, $sms_message1->isAutomated());

    $sms_message2 = new TestSmsMessage();
    $sms_message2->setAutomated(FALSE);
    $this->assertEquals(FALSE, $sms_message2->isAutomated());
  }

  /**
   * Tests UUIDs for SMS messages.
   *
   * @covers ::getUuid
   */
  public function testUuid() {
    $sms1 = new TestSmsMessage('sender1', ['1234567890'], 'test message one', [], 1);
    $sms2 = new TestSmsMessage('sender2', ['1234567890', '9087654321'], 'test message two', [], 1);

    // Test that UUIDs are different.
    $this->assertNotEquals($sms1->getUuid(), $sms2->getUuid());
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
