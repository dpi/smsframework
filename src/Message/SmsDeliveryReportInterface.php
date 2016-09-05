<?php

/**
 * @file
 * Contains \Drupal\sms\Message\SmsDeliveryReportInterface.
 */

namespace Drupal\sms\Message;

/**
 * Contains information about an SMS message.
 */
interface SmsDeliveryReportInterface {

  /**
   * Gets the gateway tracking ID for the message.
   *
   * @return string|NULL
   *   The gateway tracking ID for the message, or NULL if there is no tracking
   *   ID.
   */
  public function getMessageId();

  /**
   * Sets the gateway tracking ID for the message.
   *
   * @param string|NULL $message_id
   *   The gateway tracking ID for the message, or NULL if there is no tracking
   *   ID.
   *
   * @return $this
   *   Returns this report object for chaining.
   */
  public function setMessageId($message_id);

  /**
   * Gets the recipients for the message.
   *
   * @return string[]
   *   The recipients for the message.
   */
  public function getRecipients();

  /**
   * Sets the recipients for the message.
   *
   * @param string[] $recipients
   *   The recipients for the message.
   *
   * @return $this
   *   Returns this report object for chaining.
   */
  public function setRecipients(array $recipients);

  /**
   * Gets the status of the message.
   *
   * @return string|NULL
   *   A status code from \Drupal\sms\Message\SmsMessageReportStatus, or NULL if
   *   unknown.
   */
  public function getStatus();

  /**
   * Sets the status of the message.
   *
   * @param string|NULL $status
   *   A status code from \Drupal\sms\Message\SmsMessageReportStatus, or NULL if
   *   unknown.
   *
   * @return $this
   *   Returns this report object for chaining.
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
   * Gets the time the message was queued.
   *
   * @return integer|NULL
   *   The timestamp when the message was queued, or NULL if unknown.
   */
  public function getTimeQueued();

  /**
   * Sets the time the message was queued.
   *
   * @param integer|NULL $time
   *   The timestamp when the message was queued, or NULL if unknown.
   *
   * @return $this
   *   Returns this report object for chaining.
   */
  public function setTimeQueued($time);

  /**
   * Gets the time the message was delivered to the recipient.
   *
   * @return integer|NULL
   *   The timestamp when the message was delivered to the recipient, or NULL if
   *   unknown.
   */
  public function getTimeDelivered();

  /**
   * Sets the time the message was delivered to the recipient.
   *
   * @param integer|NULL $time
   *   The timestamp when the message was delivered to the recipient, or NULL if
   *   unknown.
   *
   * @return $this
   *   Returns this report object for chaining.
   */
  public function setTimeDelivered($time);

}
