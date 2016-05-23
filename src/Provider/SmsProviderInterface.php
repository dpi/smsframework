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
   *
   * @return \Drupal\sms\Entity\SmsMessageInterface[]
   *   The queued messages. A single message may be transformed into many.
   */
  public function queue(SmsMessageEntityInterface $sms_message);

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
   * @return \Drupal\sms\Message\SmsMessageResultInterface[]
   *   The results of the message sending operation. The message sent can be
   *   transformed into multiple messages depending on gateway implementation.
   *   Therefore this function can return multiple results.
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
