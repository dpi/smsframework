<?php

/**
 * @file
 * Contains definition of \Drupal\sms\SmsProviderInterface
 */

namespace Drupal\sms\Provider;

use Drupal\sms\Gateway\GatewayInterface;
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
   * @link \Drupal\sms\Gateway\GatewayInterface @endlink
   *
   * The original gateway code and string will often be provided in the $options
   * array as 'gateway_message_status' and 'gateway_message_status_text'.
   *
   * @param string $number
   *   The sender's mobile number.
   * @param string $reference
   *   Unique message reference code, as provided when message is sent.
   * @param int $message_status
   *   (optional) An SMS Framework message status code, according to the defined
   *   constants.
   *   Defaults to \Drupal\sms\Gateway\GatewayInterface::STATUS_UNKNOWN.
   * @param array $options
   *   (optional) Extended options passed by the receipt receiver.
   */
  public function receipt($number, $reference, $message_status = GatewayInterface::STATUS_UNKNOWN, array $options = array());

}
