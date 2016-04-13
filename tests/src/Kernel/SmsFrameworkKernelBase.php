<?php

/**
 * @file
 * Contains \Drupal\Tests\sms\Kernel\SmsFrameworkKernelBase.
 */

namespace Drupal\Tests\sms\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\sms\Tests\SmsFrameworkTestTrait;


/**
 * Base class for SMS Framework unit tests.
 */
abstract class SmsFrameworkKernelBase extends KernelTestBase {

  use SmsFrameworkTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('sms');

    /** @var \Drupal\sms\Provider\DefaultSmsProvider $provider */
    $provider = \Drupal::service('sms_provider');
    $provider
      ->getDefaultGateway()
      ->setSkipQueue(TRUE)
      ->save();
  }

}
