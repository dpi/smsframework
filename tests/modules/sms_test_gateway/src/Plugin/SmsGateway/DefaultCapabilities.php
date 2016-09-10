<?php

namespace Drupal\sms_test_gateway\Plugin\SmsGateway;

use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms\Message\SmsMessageInterface;

/**
 * Defines a gateway for testing default capabilities defined by annotation.
 *
 * This gateway does not provide any annotation details other than required
 * properties: 'id' and 'label'.
 *
 * @SmsGateway(
 *   id = "capabilities_default",
 *   label = @Translation("Default annotation capabilities")
 * )
 */
class DefaultCapabilities extends SmsGatewayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms) {
  }

}
