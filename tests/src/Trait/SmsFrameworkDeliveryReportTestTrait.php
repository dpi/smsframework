<?php

declare(strict_types=1);

namespace Drupal\Tests\sms\Trait;

use Drupal\sms\Message\SmsDeliveryReportInterface;
use Drupal\sms\Message\SmsMessageReportStatus;

/**
 * Provides common tests for SmsDeliveryReport object and entity classes.
 */
trait SmsFrameworkDeliveryReportTestTrait {

  /**
   * Tests message ID.
   *
   * @covers ::getMessageId
   * @covers ::setMessageId
   */
  public function testMessageId(): void {
    $report = $this->createDeliveryReport();
    static::assertEquals('', $report->getMessageId(), 'Default value is empty string');

    $message_id = $this->getRandomGenerator()->string();
    $report->setMessageId($message_id);

    static::assertEquals($message_id, $report->getMessageId());
  }

  /**
   * Tests recipient.
   *
   * @covers ::getRecipient
   * @covers ::setRecipient
   */
  public function testRecipient(): void {
    $report = $this->createDeliveryReport();
    static::assertEquals('', $report->getRecipient(), 'Default value is empty string');

    $recipient = $this->getRandomGenerator()->string();
    $report->setRecipient($recipient);

    static::assertEquals($recipient, $report->getRecipient());
  }

  /**
   * Tests status.
   *
   * @covers ::getStatus
   * @covers ::setStatus
   */
  public function testStatus(): void {
    $report = $this->createDeliveryReport();
    static::assertNull($report->getStatus(), 'Default value is NULL');

    $report->setStatus(SmsMessageReportStatus::QUEUED);
    static::assertEquals(SmsMessageReportStatus::QUEUED, $report->getStatus());
  }

  /**
   * Tests status message.
   *
   * @covers ::getStatusMessage
   * @covers ::setStatusMessage
   */
  public function testStatusMessage(): void {
    $report = $this->createDeliveryReport();
    static::assertEquals('', $report->getStatusMessage(), 'Default value is empty string');

    $status_message = $this->getRandomGenerator()->string();
    $report->setStatusMessage($status_message);

    static::assertEquals($status_message, $report->getStatusMessage());
  }

  /**
   * Tests status time.
   *
   * @covers ::getStatusTime
   * @covers ::setStatusTime
   */
  public function testStatusTime(): void {
    $report = $this->createDeliveryReport();
    static::assertNull($report->getStatusTime(), 'Default value is NULL');

    $time = 123123123;
    $report->setStatusTime($time);

    static::assertEquals($time, $report->getStatusTime());
  }

  /**
   * Tests time queued.
   *
   * @covers ::getTimeQueued
   * @covers ::setTimeQueued
   */
  public function testTimeQueued(): void {
    $report = $this->createDeliveryReport();
    static::assertNull($report->getTimeQueued(), 'Default value is NULL');

    $time = 123123123;
    $report->setTimeQueued($time);

    static::assertEquals($time, $report->getTimeQueued());
  }

  /**
   * Tests time delivered.
   *
   * @covers ::getTimeDelivered
   * @covers ::setTimeDelivered
   */
  public function testTimeDelivered(): void {
    $report = $this->createDeliveryReport();
    static::assertNull($report->getTimeDelivered(), 'Default value is NULL');

    $time = 123123123;
    $report->setTimeDelivered($time);

    static::assertEquals($time, $report->getTimeDelivered());
  }

  /**
   * Creates an SMS delivery report for testing.
   *
   * @return \Drupal\sms\Message\SmsDeliveryReportInterface
   *   A delivery report for testing.
   */
  abstract protected function createDeliveryReport(): SmsDeliveryReportInterface;

}
