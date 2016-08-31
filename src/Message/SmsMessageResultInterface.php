<?php

/**
 * @file
 * Contains Drupal\sms\Message\SmsMessageResultInterface
 */

namespace Drupal\sms\Message;

/**
 * Contains information on SMS message results.
 */
interface SmsMessageResultInterface {

  /**
   * Gets the status of the message.
   *
   * @return bool
   *   True if message was sent without errors. False if message could not be
   *     sent.
   */
  public function getStatus();

  /**
   * Gets the translated error message for failed attempts.
   *
   * @return string
   *  The translated error message if the status is FALSE.
   */
  public function getErrorMessage();

  /**
   * Gets the delivery reports for all recipients.
   *
   * @return \Drupal\sms\Message\SmsDeliveryReportInterface[]
   *   An array of delivery reports for all recipients keyed by the recipients'
   *   number.
   */
  public function getReports();

  /**
   * Gets the credit balance after the SMS was sent.
   *
   * @return int
   *   The value of the balance. This number is in the SMS gateway's chosen
   *   denomination.
   */
  public function getBalance();

  /**
   * Gets the SMS credits used for this transaction.
   *
   * @return int
   *   The amount of SMS credits used in the gateway's chosen denomination.
   */
  public function getCreditsUsed();

  /**
   * Returns this result report as an array.
   *
   * @return array
   */
  public function toArray();

  /**
   * Gets the delivery report for a particular recipient.
   *
   * @param string $recipient
   *   The number of the recipient for which the report is to be retrieved.
   *
   * @return \Drupal\sms\Message\SmsDeliveryReportInterface
   *   A delivery report object.
   *
   * @see SmsMessageResultInterface::getReports()
   */
  public function getReport($recipient);

  /**
   * Get all messages associated with this result.
   *
   * @return \Drupal\sms\Message\SmsMessageInterface[]
   *   The messages associated with this result.
   */
  public function getMessages();

  /**
   * Set the messages on this result.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface[]
   *   The messages to associate with this result.
   *
   * @return $this
   *   Returns this result for chaining.
   */
  public function setMessages(array $messages);

}
