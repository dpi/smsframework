<?php

namespace Drupal\Tests\sms\Unit\Message;

use Drupal\Tests\sms\Functional\SmsFrameworkMessageResultTestTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\sms\Message\SmsMessageResult;

/**
 * Unit tests for results.
 *
 * @group SMS Framework
 * @coversDefaultClass \Drupal\sms\Message\SmsMessageResult
 */
class SmsFrameworkResultUnitTest extends UnitTestCase {

  use SmsFrameworkMessageResultTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function createMessageResult() {
    return new SmsMessageResult();
  }

}
