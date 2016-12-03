<?php

namespace Drupal\sms\Plugin;

use Drupal\sms\Message\SmsMessageInterface;

/**
 * Interface for gateways which can receive SMS messages.
 */
interface SmsGatewayPluginIncomingInterface {

  /**
   * Process a SMS message from this gateway.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms_message
   *   A SMS message.
   *
   * @return \Drupal\sms\Message\SmsMessageResultInterface
   *   The result of receiving the message.
   */
  public function incoming(SmsMessageInterface $sms_message);

}
