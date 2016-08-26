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
   * @return boolean|NULL
   *   - TRUE if message sent without errors.
   *   - FALSE if message could not be sent.
   *   - NULL if no status was retrieved.
   */
  public function getStatus();

  /**
   * Sets the status of the message.
   *
   * @param boolean|NULL $status
   *   - TRUE if message sent without errors.
   *   - FALSE if message could not be sent.
   *   - NULL if no status was retrieved.
   *
   * @return $this
   *   Returns this result object for chaining.
   */
  public function setStatus($status);

  /**
   * Gets the translated error message for failed attempts.
   *
   * @return string
   *   The translated error message if the status is FALSE.
   */
  public function getErrorMessage();

  /**
   * Sets the translated error message for failed attempts.
   *
   * @param string $error_message
   *   The translated error message if the status is FALSE.
   *
   * @return $this
   *   Returns this result object for chaining.
   */
  public function setErrorMessage($error_message);

  /**
   * Gets the delivery report for a particular recipient.
   *
   * @param string $recipient
   *   The number of the recipient for which the report is to be retrieved.
   *
   * @return \Drupal\sms\Message\SmsDeliveryReportInterface|NULL
   *   A delivery report object.
   *
   * @see SmsMessageResultInterface::getReports()
   */
  public function getReport($recipient);

  /**
   * Gets the delivery reports for all recipients.
   *
   * @return \Drupal\sms\Message\SmsDeliveryReportInterface[]
   *   An array of delivery reports for all recipients keyed by the recipients'
   *   number.
   */
  public function getReports();

  /**
   * Sets the delivery reports for all recipients.
   *
   * @param \Drupal\sms\Message\SmsDeliveryReportInterface[] $reports
   *   An array of delivery reports for all recipients keyed by the recipients'
   *   number.
   *
   * @return $this
   *   Returns this result object for chaining.
   */
  public function setReports(array $reports);

  /**
   * Gets the credit balance after the SMS was sent.
   *
   * @return integer
   *   The value of the balance. This number is in the SMS gateway's chosen
   *   denomination.
   */
  public function getBalance();

  /**
   * Sets the credit balance after the SMS was sent.
   *
   * @param integer $balance
   *   The value of the balance. This number is in the SMS gateway's chosen
   *   denomination.
   *
   * @return $this
   *   Returns this result object for chaining.
   */
  public function setBalance($balance);

  /**
   * Gets the SMS credits used for this transaction.
   *
   * @return int
   *   The amount of SMS credits used in the gateway's chosen denomination.
   */
  public function getCreditsUsed();

  /**
   * Sets the SMS credits used for this transaction.
   *
   * @param integer $credits_used
   *   The amount of SMS credits used in the gateway's chosen denomination.
   *
   * @return $this
   *   Returns this result object for chaining.
   */
  public function setCreditsUsed($credits_used);

}
