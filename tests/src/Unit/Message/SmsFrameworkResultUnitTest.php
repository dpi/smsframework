<?php

namespace Drupal\Tests\sms\Unit\Message;

use Drupal\sms\Exception\SmsException;
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
   * Tests error.
   *
   * @covers ::getError
   * @covers ::setError
   */
  public function testError() {
    $result = $this->createResult();
    $this->assertNull($result->getError(), 'Default value is NULL');

    $error = $this->getRandomGenerator()->string();
    $return = $result->setError($error);

    $this->assertTrue($return instanceof SmsMessageResultInterface);
    $this->assertEquals($error, $result->getError());
  }

  /**
   * Tests error message.
   *
   * @covers ::getErrorMessage
   * @covers ::setErrorMessage
   */
  public function testErrorMessage() {
    $result = $this->createResult();
    $this->assertEquals('', $result->getErrorMessage(), 'Default value is empty string');

    $error_message = $this->getRandomGenerator()->string();
    $return = $result->setErrorMessage($error_message);

    $this->assertTrue($return instanceof SmsMessageResultInterface);
    $this->assertEquals($error_message, $result->getErrorMessage());
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
    $balance = 'foobar';
    $result = $this->createResult();

    $this->setExpectedException(SmsException::class, 'Credit balance set is a string');
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
    $used = 'foobar';
    $result = $this->createResult();

    $this->setExpectedException(SmsException::class, 'Credit used is a string');
    $result->setCreditsUsed($used);
  }

  /**
   * Create a result for testing.
   *
   * @return \Drupal\sms\Message\SmsMessageResultInterface
   *   A result for testing.
   */
  protected function createResult() {
    return new SmsMessageResult();
  }

}
