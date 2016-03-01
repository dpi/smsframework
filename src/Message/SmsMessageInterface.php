<?php

/**
 * @file
 * Contains definition of \Drupal\sms\Message\SmsMessageInterface
 */

namespace Drupal\sms\Message;

/**
 * Contains information about an SMS message.
 */
interface SmsMessageInterface {

  // Message status codes
  // 0=Unknown, 2xx=Positive, 3xx=Positive/Neutral (context-dependent), 4xx=Negative

  /**
   * Status Unknown.
   *
   * A message would have this to indicate unknown status.
   */
  const STATUS_UNKNOWN = 0;

  /**
   * Status OK.
   *
   * A message with this status indicates it was successfully sent.
   */
  const STATUS_OK = 200;

  /**
   * Status DELIVERED.
   *
   * A message with this status indicates it was successfully delivered.
   */
  const STATUS_DELIVERED = 202;

  /**
   * Status QUEUED.
   *
   * A message with this status indicates it was successfully queued for sending.
   */
  const STATUS_QUEUED = 302;

  /**
   * Status ERROR.
   *
   * A message with this status indicates it could not be sent (routing reasons).
   */
  const STATUS_ERROR = 400;

  /**
   * Status NO_CREDIT.
   *
   * A message with this status indicates it could not be sent due to low credit
   * balance.
   */
  const STATUS_NO_CREDIT = 402;

  /**
   * Status EXPIRED.
   *
   * A message with this status indicates it has expired and has not been sent.
   */
  const STATUS_EXPIRED = 408;

  /**
   * Gets the list of recipients of this SMS message.
   *
   * @return array
   */
  public function getRecipients();

  /**
   * Add a recipient to the SMS message.
   *
   * @param string $recipient
   *   The recipient to add.
   *
   * @return $this
   *   The called SMS message object.
   */
  public function addRecipient($recipient);

  /**
   * Adds multiple recipients to the SMS message.
   *
   * @param array $recipients
   *   An array of recipients to add.
   *
   * @return $this
   *   The called SMS message object.
   */
  public function addRecipients(array $recipients);

    /**
   * Removes a recipient from the SMS message.
   *
   * @param string $recipient
   *   The recipient to remove.
   *
   * @return $this
   *   The called SMS message object.
   */
  public function removeRecipient($recipient);

  /**
   * Gets the options for building or sending this SMS message.
   *
   * @return array
   */
  public function getOptions();

  /**
   * Gets the option specified by the key $name.
   *
   * @param string
   *   The name of the option.
   *
   * @return mixed
   *   Get the value of the option.
   */
  public function getOption($name);

  /**
   * Sets an option for this SMS message.
   *
   * @param string $name
   *   The name of the option
   * @param mixed $value
   *   The value of the option.
   *
   * @return $this
   *   The called SMS message object.
   */
  public function setOption($name, $value);

  /**
   * Removes an option from this SMS message.
   *
   * @param string $name
   *   The name of the option.
   *
   * @return $this
   *   The called SMS message object.
   */
  public function removeOption($name);

  /**
   * Gets the name of the sender of this SMS message.
   *
   * @return string
   */
  public function getSender();

  /**
   * Set the name of the sender of this SMS message.
   *
   * @param string $sender
   *   The name of the sender.
   *
   * @return $this
   *   The called SMS message object.
   */
  public function setSender($sender);

  /**
   * Gets the message to be sent.
   *
   * @return string
   */
  public function getMessage();

  /**
   * Set the message to be sent.
   *
   * @param string $message
   *   The message to be sent.
   *
   * @return $this
   *   The called SMS message object.
   */
  public function setMessage($message);

  /**
   * Gets the UUID of the SMS object.
   *
   * @return string
   */
  public function getUuid();

  /**
   * Gets the user who created the SMS message.
   *
   * @return int
   */
  public function getUid();

  /**
   * Set the user who created the SMS message
   * @param int $uid
   *
   *
   * @return $this
   *   The called SMS message object.
   */
  public function setUid($uid);

  /**
   * Sets whether this SMS message was generated automatically.
   *
   * @param bool $automated
   *   Whether this SMS message was generated automatically. Set to FALSE if the
   *   message is created due to direct action.
   *
   * @return $this
   *   The called SMS message object.
   */
  public function setAutomated($automated);

  /**
   * Gets whether this SMS message was generated automatically.
   *
   * @return bool
   *   Whether this SMS message was generated automatically.
   */
  public function isAutomated();

}
