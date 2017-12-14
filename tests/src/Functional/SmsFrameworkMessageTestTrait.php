<?php

namespace Drupal\Tests\sms\Functional;

use Drupal\sms\Direction;
use Drupal\sms\Entity\SmsMessageInterface;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Message\SmsMessageResult;

/**
 * SMS Message object test trait.
 *
 * Covers methods as found in \Drupal\sms\Message\SmsMessageInterface.
 *
 * This trait requires a createSmsMessage method to be implemented on the
 * imported class.
 */
trait SmsFrameworkMessageTestTrait {

  /**
   * Tests sender name.
   */
  public function testSender() {
    $sender = $this->randomMachineName();
    $sms_message = $this->createSmsMessage();
    $sms_message->setSender($sender);
    $this->assertEquals($sender, $sms_message->getSender());
  }

  /**
   * Tests sender phone number.
   *
   * @covers ::getSenderNumber
   * @covers ::setSenderNumber
   */
  public function testSenderNumber() {
    $number = '1234567890';
    $sms_message = $this->createSmsMessage();
    $sms_message->setSenderNumber($number);
    $this->assertEquals($number, $sms_message->getSenderNumber());
  }

  /**
   * Tests recipients for SMS messages.
   *
   * @covers ::getMessage
   * @covers ::setMessage
   */
  public function testMessage() {
    $message = $this->randomMachineName();
    $sms_message1 = $this->createSmsMessage();
    $sms_message1->setMessage($message);
    $this->assertEquals($message, $sms_message1->getMessage());
  }

  /**
   * Tests recipients for SMS messages.
   *
   * @covers ::getRecipients
   */
  public function testRecipients() {
    $sms_message0 = $this->createSmsMessage();

    $sms_message1 = $this->createSmsMessage();
    $sms_message1->addRecipients(['1234567890']);

    $sms_message2 = $this->createSmsMessage();
    $sms_message2->addRecipients(['1234567890', '9087654321']);

    // Test that getRecipients return arrays.
    $this->assertEquals([], $sms_message0->getRecipients());
    $this->assertEquals(['1234567890'], $sms_message1->getRecipients());
    $this->assertEquals(['1234567890', '9087654321'], $sms_message2->getRecipients());
  }

  /**
   * Tests adding recipients to SMS messages.
   *
   * @covers ::addRecipient
   */
  public function testRecipientAdd() {
    $recipient1 = '123123123';
    $recipient2 = '456456456';
    $sms_message1 = $this->createSmsMessage();
    $sms_message1
      ->addRecipient($recipient1)
      ->addRecipient($recipient2);
    $this->assertEquals([$recipient1, $recipient2], $sms_message1->getRecipients());

    // Check duplicate recipients are not added.
    $sms_message2 = $this->createSmsMessage();
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
    $sms_message2 = $this->createSmsMessage();
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
    $sms_message1 = $this->createSmsMessage();
    $sms_message1
      ->addRecipient($recipient1)
      ->addRecipient($recipient2);
    $sms_message1->removeRecipient($recipient1);
    $this->assertEquals([$recipient2], $sms_message1->getRecipients());
  }

  /**
   * Tests removing multiple recipients from SMS messages.
   *
   * @covers ::removeRecipients
   */
  public function testRecipientsRemove() {
    // Test multiple recipient remove.
    $recipients = ['123123123', '456456456', '234234234'];
    $sms_message = $this->createSmsMessage();
    $sms_message
      ->addRecipients($recipients);
    $this->assertEquals($recipients, $sms_message->getRecipients());
    $sms_message
      ->removeRecipients(['123123123', '234234234']);
    $this->assertEquals(['456456456'], $sms_message->getRecipients());
  }

  /**
   * Tests direction of SMS messages.
   *
   * @covers ::getDirection
   * @covers ::setDirection
   */
  public function testDirection() {
    $sms_message2 = $this->createSmsMessage()
      ->setDirection(Direction::OUTGOING);
    $this->assertEquals(Direction::OUTGOING, $sms_message2->getDirection());

    $sms_message3 = $this->createSmsMessage()
      ->setDirection(Direction::INCOMING);
    $this->assertEquals(Direction::INCOMING, $sms_message3->getDirection());
  }

  /**
   * Tests recipients for SMS messages.
   *
   * @covers ::setOption
   * @covers ::getOptions
   */
  public function testOptionsSet() {
    $options = ['foo' => $this->randomMachineName()];
    $sms_message1 = $this->createSmsMessage();
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
    $sms_message1 = $this->createSmsMessage();
    $sms_message1->setOption('foo', $options['foo']);
    $sms_message1->setOption('bar', $options['bar']);
    $sms_message1->removeOption('foo');
    unset($options['foo']);
    $this->assertEquals($options, $sms_message1->getOptions());
  }

  /**
   * Tests result and reports for SMS messages.
   *
   * @covers ::getResult
   * @covers ::setResult
   * @covers ::getReport
   * @covers ::getReports
   */
  public function testResults() {
    $error_message = $this->getRandomGenerator()->string();
    $recipients = ['2345678901', '1234567890'];
    $reports = array_combine($recipients, array_map(function ($recipient) {
      return (new SmsDeliveryReport())
        ->setRecipient($recipient)
        ->setStatus(SmsMessageReportStatus::DELIVERED);
    }, $recipients));
    $result = (new SmsMessageResult())
      ->setErrorMessage($error_message)
      ->setReports($reports);
    $sms_message = $this->createSmsMessage()
      ->addRecipients($recipients)
      ->setResult($result);

    $result_actual = $sms_message->getResult();
    $this->assertEquals($error_message, $result_actual->getErrorMessage());
    $this->assertEquals($result->getErrorMessage(), $result_actual->getErrorMessage());
    $this->assertEquals($reports['1234567890']->getStatus(), $sms_message->getReport('1234567890')->getStatus());
    $this->assertEquals($reports['2345678901']->getStatus(), $sms_message->getReport('2345678901')->getStatus());
  }

  /**
   * Tests adding recipients to SMS messages.
   *
   * @covers ::getUid
   * @covers ::setUid
   */
  public function testUid() {
    $sms_message1 = $this->createSmsMessage();

    // Default value.
    $this->assertEquals($sms_message1->getUid(), NULL);

    // Set value.
    $sms_message2 = $this->createSmsMessage();
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
    $sms_message1 = $this->createSmsMessage();

    // Default.
    $this->assertEquals(TRUE, $sms_message1->isAutomated());

    $sms_message2 = $this->createSmsMessage();
    $sms_message2->setAutomated(FALSE);
    $this->assertEquals(FALSE, $sms_message2->isAutomated());
  }

  /**
   * Tests UUIDs for SMS messages.
   *
   * @covers ::getUuid
   */
  public function testUuid() {
    $sms1 = $this->createSmsMessage();
    $sms2 = $this->createSmsMessage();;

    // Test that UUIDs are different.
    $this->assertNotEquals($sms1->getUuid(), $sms2->getUuid());
  }

  /**
   * Tests chunk by recipients.
   *
   * @covers ::chunkByRecipients
   */
  public function testsChunkByRecipients() {
    $sms_message = $this->createSmsMessage();
    $sms_message->addRecipients(['100', '200', '300', '400', '500']);
    $sms_messages = $sms_message->chunkByRecipients(2);
    $this->assertEquals(3, count($sms_messages));
    $this->assertEquals(['100', '200'], $sms_messages[0]->getRecipients());
    $this->assertEquals(['300', '400'], $sms_messages[1]->getRecipients());
    $this->assertEquals(['500'], $sms_messages[2]->getRecipients());
  }

}
