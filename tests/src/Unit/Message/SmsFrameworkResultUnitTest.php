<?php

namespace Drupal\Tests\sms\Unit\Message;

use Drupal\Tests\UnitTestCase;
use Drupal\sms\Message\SmsMessageResultInterface;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsDeliveryReportInterface;
use Drupal\sms\Message\SmsDeliveryReport;

/**
 * Unit tests for results.
 *
 * @group SMS Framework
 * @coversDefaultClass \Drupal\sms\Message\SmsMessageResult
 */
class SmsFrameworkResultUnitTest extends UnitTestCase {

  /**
   * Tests status.
   *
   * @covers ::getStatus
   * @covers ::setStatus
   */
  public function testStatus() {
    $result = $this->createResult();
    $this->assertNull($result->getStatus(), 'Default value is NULL');

    $status = $this->getRandomGenerator()->string();
    $return = $result->setStatus($status);

    $this->assertTrue($return instanceof SmsMessageResultInterface);
    $this->assertEquals($status, $result->getStatus());
  }

  /**
   * Tests status message.
   *
   * @covers ::getStatusMessage
   * @covers ::setStatusMessage
   */
  public function testStatusMessage() {
    $result = $this->createResult();
    $this->assertEquals('', $result->getStatusMessage(), 'Default value is empty string');

    $status_message = $this->getRandomGenerator()->string();
    $return = $result->setStatusMessage($status_message);

    $this->assertTrue($return instanceof SmsMessageResultInterface);
    $this->assertEquals($status_message, $result->getStatusMessage());
  }

  /**
   * Tests get result.
   *
   * @covers ::getReport
   */
  public function testGetResult() {
    $result = $this->createResult();
    $recipient = '123123123';
    $this->assertNull($result->getReport($recipient), 'No report found');

    $report = (new SmsDeliveryReport())
      ->setRecipient($recipient);
    $result->setReports([$report]);

    $this->assertTrue($result->getReport($recipient) instanceof SmsDeliveryReportInterface, 'Report found');
  }

  /**
   * Tests set results.
   *
   * @covers ::getReports
   * @covers ::setReports
   */
  public function testResults() {
    $result = $this->createResult();
    $recipient = '123123123';

    $report = (new SmsDeliveryReport())
      ->setRecipient($recipient);
    $return = $result->setReports([$report]);
    $this->assertTrue($return instanceof SmsMessageResultInterface);

    $reports = $result->getReports();
    $this->assertEquals(1, count($reports));
    $this->assertTrue($reports[0] instanceof SmsDeliveryReportInterface);
  }

  /**
   * Tests credits balance.
   *
   * @covers ::getCreditsBalance
   * @covers ::setCreditsBalance
   */
  public function testCreditsBalance() {
    $result = $this->createResult();
    $this->assertNull($result->getCreditsBalance(), 'No credit balance set');

    $balance = 13.37;
    $return = $result->setCreditsBalance($balance);
    $this->assertTrue($return instanceof SmsMessageResultInterface);
    $this->assertEquals($balance, $result->getCreditsBalance());
  }

  /**
   * Tests credits balance set is wrong type.
   *
   * @covers ::setCreditsBalance
   */
  public function testCreditsBalanceIncorrectType() {
    $balance = 1337;
    $result = $this->createResult();

    $this->setExpectedException(\Drupal\sms\Exception\SmsException::class, 'Credit balance set is a integer');
    $result->setCreditsBalance($balance);
  }

  /**
   * Tests credits used.
   *
   * @covers ::getCreditsUsed
   * @covers ::setCreditsUsed
   */
  public function testCreditsUsed() {
    $result = $this->createResult();
    $this->assertNull($result->getCreditsUsed(), 'No credits used set');

    $used = 13.37;
    $return = $result->setCreditsUsed($used);
    $this->assertTrue($return instanceof SmsMessageResultInterface);
    $this->assertEquals($used, $result->getCreditsUsed());
  }

  /**
   * Tests credits used set is wrong type.
   *
   * @covers ::setCreditsUsed
   */
  public function testCreditsUsedIncorrectType() {
    $used = 1337;
    $result = $this->createResult();

    $this->setExpectedException(\Drupal\sms\Exception\SmsException::class, 'Credit used is a integer');
    $result->setCreditsUsed($used);
  }

  /**
   * Create a result for testing.
   *
   * @return \Drupal\sms\Message\SmsMessageResultInterface
   */
  protected function createResult() {
    return new SmsMessageResult();
  }

}
