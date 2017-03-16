<?php

namespace Drupal\Tests\sms\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\sms\Tests\SmsFrameworkTestTrait;

/**
 * Base test class for functional browser tests.
 */
abstract class SmsFrameworkBrowserTestBase extends BrowserTestBase {

  use SmsFrameworkTestTrait;

  public static $modules = [
    'sms',
    'sms_test_gateway',
    'telephone',
    'dynamic_entity_reference',
  ];

}
