<?php

/**
 * @file
 * Contains definition of \Drupal\sms\Provider\SmsProviderInterface
 */

namespace Drupal\sms\Provider;

use Drupal\sms\Entity\SmsGatewayInterface;
use Drupal\sms\Message\SmsMessageInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\sms\Entity\SmsMessageInterface as SmsMessageEntityInterface;

/**
 * Provides an interface for sending messages
 */
interface SmsProviderInterface {

  /**
   * Queue a SMS message for sending or receiving.
   *
   * @param \Drupal\sms\Entity\SmsMessageInterface $sms_message
   *   A SMS message entity.
   */
  public function queue(SmsMessageEntityInterface &$sms_message);

  /**
   * Queue a standard SMS message for receiving.
   *
   * @todo Remove if standard message gets a direction property.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms_message
   *   A standard SMS message.
   */
  public function queueIn(SmsMessageInterface $sms_message);

  /**
   * Queue a standard SMS message for sending.
   *
   * @todo Remove if standard message gets a direction property.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms_message
   *   A standard SMS message.
   */
  public function queueOut(SmsMessageInterface $sms_message);

  /**
   * Sends an SMS using the active gateway.
   *
   * It is preferred to use queue method over directly invoking send().
   *
   * @param \Drupal\sms\Message\SmsMessageInterface
   *   The message to be sent.
   *
   * @return \Drupal\sms\Message\SmsMessageResultInterface|false
   *   The result of the message sending operation or false if the process was
   *   aborted by a pre-process hook.
   */
  public function send(SmsMessageInterface $sms);

  /**
   * Handles a message received by the server.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface
   *   The message received.
   */
  public function incoming(SmsMessageInterface $sms_message);

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

  /**
   * Handles delivery reports returning to the SMS provider from gateways.
   *
   * Allows gateways plugins to correctly parse delivery reports and return a
   * standard format for processing and storage.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request that contains the delivery report.
   * @param \Drupal\sms\Entity\SmsGatewayInterface $gateway
   *   The SMS Gateway designated to process the delivery report.
   * @param array $options
   *   (optional) Additional options required for parsing the Delivery Report.
   */
  public function processDeliveryReport(Request $request, SmsGatewayInterface $gateway, array $options = []);

}
