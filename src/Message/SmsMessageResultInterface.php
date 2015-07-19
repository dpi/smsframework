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
   * Gets the delivery report for all recipients.
   *
   * @return array
   *   The report for all recipients. This report is an array keyed by the
   *   recipients' numbers. The value is also an array with the following keys:
   *   - status: true if message was successfully sent, false otherwise.
   *   - message_id: The message id if the message was successfully sent to that
   *       recipient. Zero means message was not sent to that recipient.
   *   - error_code: The error code number for a specific message.
   *   - error_message: The description of the error message.
   *   - gateway_error_code: The original error code from the SMS gateway.
   *   - gateway_error_message: The original error message from the SMS gateway.
   */
  public function getReport();

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
   * Gets the specific report for a particular recipient.
   *
   * @param string $recipient
   *   The number of the recipient for which the report is to be retrieved.
   * @return array
   *   An array containing the message report
   *   @link see SmsMessageResultInterface::getReport() @endlink
   */
  public function getReportFor($recipient);

}
