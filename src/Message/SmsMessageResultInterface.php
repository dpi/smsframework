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
   * @return string|NULL
   *   A status code from \Drupal\sms\Message\SmsMessageStatus, or NULL if
   *   unknown.
   */
  public function getStatus();

  /**
   * Sets the status of the message.
   *
   * @param string $status|NULL
   *   A status code from \Drupal\sms\Message\SmsMessageStatus, or NULL if
   *   unknown.
   *
   * @return $this
   *   Returns this result object for chaining.
   */
  public function setStatus($status);

  /**
   * Gets the status message.
   *
   * @return string
   *   The status message as provided by the gateway API.
   */
  public function getStatusMessage();

  /**
   * Sets the status message.
   *
   * @param string $message
   *   The status message as provided by the gateway API.
   *
   * @return $this
   *   Returns this report object for chaining.
   */
  public function setStatusMessage($message);

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

  /**
   * Gets the delivery report for a particular recipient.
   *
   * @param string $recipient
   *   The number of the recipient for which the report is to be retrieved.
   *
   * @return \Drupal\sms\Message\SmsDeliveryReportInterface|NULL
   *   A delivery report object, or NULL if there is no report for the
   *   recipient.
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
   * Gets the credit balance after this transaction.
   *
   * @return float|NULL
   *   The credit balance after the message is processed, or NULL if unknown.
   */
  public function getCreditsBalance();

  /**
   * Sets the credit balance after this transaction.
   *
   * @param float|NULL $balance
   *   The credit balance after the message is processed, or NULL if unknown.
   *
   * @return $this
   *   Returns this result object for chaining.
   */
  public function setCreditsBalance($balance);

  /**
   * Gets the credits consumed for this transaction.
   *
   * @return float|NULL
   *   The credits consumed for this transaction, or NULL if unknown.
   */
  public function getCreditsUsed();

  /**
   * Sets the credits consumed for this transaction.
   *
   * @param float|NULL $credits_used
   *   The credits consumed for this transaction, or NULL if unknown.
   *
   * @return $this
   *   Returns this result object for chaining.
   */
  public function setCreditsUsed($credits_used);

}
