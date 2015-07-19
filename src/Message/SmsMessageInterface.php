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
   * @return array
   */
  public function getOption($name);

  /**
   * Gets the name of the sender of this SMS message.
   *
   * @return string
   */
  public function getSender();

  /**
   * Gets the message to be sent.
   *
   * @return string
   */
  public function getMessage();

}
