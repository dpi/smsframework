<?php

/**
 * @file
 * Contains definition of \Drupal\sms\SmsProviderInterface
 */

namespace Drupal\sms\Provider;

use Drupal\sms\Message\SmsDeliveryReportInterface;
use Drupal\sms\Plugin\SmsGatewayPluginInterface;
use Drupal\sms\Message\SmsMessageInterface;

/**
 * Provides an interface for sending messages
 */
interface SmsProviderInterface {

  /**
   * Sends an SMS using the active gateway.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface
   *   The message to be sent.
   * @param array
   *   Additional options to be passed to the SMS gateway.
   *
   * @return \Drupal\sms\Message\SmsMessageResultInterface|false
   *   The result of the message sending operation or false if the process was
   *   aborted by a pre-process hook.
   */
  public function send(SmsMessageInterface $sms, array $options);

  /**
   * Handles a message received by the server.
   *
   * Allows gateways to pass messages in a standard format for processing.
   * Every implementation of hook_sms_incoming() will be invoked by this method.
   *
   * Additionally, 'sms_incoming' rules event will be invoked if rules module is
   * enabled.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface
   *   The message received.
   * @param array
   *   Additional options to be passed to the SMS gateway.
   */
  public function incoming(SmsMessageInterface $sms, array $options);

  /**
   * Handles responses to the SMS provider from gateways.
   *
   * Allows gateways modules to pass message receipts and other responses to
   * messages in a standard format for processing, and provides a basic set of
   * status codes for common code handling.
   *
   * Allowed message status codes are defined as constants in
   * @link \Drupal\sms\Plugin\SmsGatewayPluginInterface @endlink
   *
   * The original gateway code and string will often be provided in the $options
   * array as 'gateway_message_status' and 'gateway_message_status_text'.
   *
   * @param \Drupal\sms\Message\SmsDeliveryReportInterface[] $reports
   *   An array of the delivery reports that have been received.
   * @param array $options
   *   (optional) Extended options passed by the receipt receiver.
   */
  public function receipt(array $reports, array $options = []);

}
