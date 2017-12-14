<?php

namespace Drupal\Tests\sms\Functional;

use Drupal\sms\Exception\SmsException;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsDeliveryReportInterface;
use Drupal\sms\Message\SmsMessageResultInterface;

/**
 * Provides common tests for SmsMessageResult object and entity classes.
 */
trait SmsFrameworkMessageResultTestTrait {

  /**
   * Tests error.
   *
   * @covers ::getError
   * @covers ::setError
   */
  public function testError() {
    $result = $this->createMessageResult();
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
    $result = $this->createMessageResult();
    $this->assertEquals('', $result->getErrorMessage(), 'Default value is empty string');

    $error_message = $this->getRandomGenerator()->string();
    $return = $result->setErrorMessage($error_message);

    $this->assertTrue($return instanceof SmsMessageResultInterface);
    $this->assertEquals($error_message, $result->getErrorMessage());
  }

  /**
   * Tests get report for a recipient.
   *
   * @covers ::getReport
   */
  public function testGetReport() {
    $result = $this->createMessageResult();
    $recipient = '123123123';
    $this->assertNull($result->getReport($recipient), 'No report found');

    $report = (new SmsDeliveryReport())
      ->setRecipient($recipient);
    $result->setReports([$report]);

    $this->assertTrue($result->getReport($recipient) instanceof SmsDeliveryReportInterface, 'Report found');
  }

  /**
   * Tests setting and getting the list of reports.
   *
   * @covers ::getReports
   * @covers ::setReports
   */
  public function testReports() {
    $result = $this->createMessageResult();
    $recipient = '123123123';

    $report = (new SmsDeliveryReport())
      ->setRecipient($recipient);
    $return = $result->setReports([$report]);
    $this->assertTrue($return instanceof SmsMessageResultInterface);

    $reports = $result->getReports();
    $this->assertEquals(1, count($reports));
    $this->assertTrue($reports[0] instanceof SmsDeliveryReportInterface);

    // Verify that a second ::setReports() call clears what was there before.
    $report2 = (new SmsDeliveryReport())
      ->setRecipient('2345678901');
    $result->setReports([$report2]);

    $reports = $result->getReports();
    $this->assertEquals(1, count($reports));
  }

  /**
   * Tests adding a report to the list of reports.
   *
   * @covers ::addReport
   */
  public function testAddReport() {
    $result = $this->createMessageResult();

    $this->assertEquals(0, count($result->getReports()), 'There are zero reports.');

    $report = (new SmsDeliveryReport())
      ->setRecipient('123123123');

    $return = $result->addReport($report);
    $this->assertTrue($return instanceof SmsMessageResultInterface, 'Return type is a result object');

    $this->assertEquals(1, count($result->getReports()), 'There is one report.');
  }

  /**
   * Tests credits balance.
   *
   * @covers ::getCreditsBalance
   * @covers ::setCreditsBalance
   */
  public function testCreditsBalance() {
    $result = $this->createMessageResult();
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
    $result = $this->createMessageResult();

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
    $result = $this->createMessageResult();
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
    $result = $this->createMessageResult();

    $this->setExpectedException(SmsException::class, 'Credit used is a string');
    $result->setCreditsUsed($used);
  }

  /**
   * Creates a message result for testing.
   *
   * @return \Drupal\sms\Message\SmsMessageResultInterface
   *   A result for testing.
   */
  abstract protected function createMessageResult();

}
