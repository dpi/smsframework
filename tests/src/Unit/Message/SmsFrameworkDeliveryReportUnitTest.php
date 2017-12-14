<?php

namespace Drupal\Tests\sms\Unit\Message;

use Drupal\Tests\sms\Functional\SmsFrameworkDeliveryReportTestTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\sms\Message\SmsDeliveryReport;

/**
 * Unit tests for delivery reports.
 *
 * @group SMS Framework
 * @coversDefaultClass \Drupal\sms\Message\SmsDeliveryReport
 */
class SmsFrameworkDeliveryReportUnitTest extends UnitTestCase {

  use SmsFrameworkDeliveryReportTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function createDeliveryReport() {
    return new SmsDeliveryReport();
  }

}
