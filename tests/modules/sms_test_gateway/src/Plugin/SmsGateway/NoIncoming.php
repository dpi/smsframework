<?php

declare(strict_types=1);

namespace Drupal\sms_test_gateway\Plugin\SmsGateway;

use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResultInterface;
use Drupal\sms\Plugin\SmsGatewayPluginBase;

/**
 * Defines a gateway which does not implement incoming messages.
 *
 * @SmsGateway(
 *   id = "memory_noincoming",
 *   label = @Translation("No Incoming"),
 * )
 */
final class NoIncoming extends SmsGatewayPluginBase {

  public function send(SmsMessageInterface $sms): SmsMessageResultInterface {
    throw new \LogicException('No-op');
  }

}
