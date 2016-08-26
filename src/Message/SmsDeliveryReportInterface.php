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
   * Gets the message ID for the message.
   *
   * Usually message IDs are returned by the gateway to identify a message sent
   * and should be unique for a particular combination of message and recipient.
   *
   * @return string
   */
  public function getMessageId();

  /**
   * Sets the message ID for the message.
   *
   * @param string $message_id
   *   Usually message IDs are returned by the gateway to identify a message
   *   sent and should be unique for a particular combination of message and
   *   recipient.
   *
   * @return $this
   *   Returns this report object for chaining.
   */
  public function setMessageId($message_id);

  /**
   * Gets the recipient for which the message was intended.
   *
   * @return string
   *   The recipient for which the message was intended.
   */
  public function getRecipient();

  /**
   * Sets the recipient for which the message was intended.
   *
   * @param string $recipient
   *   The recipient for which the message was intended.
   *
   * @return $this
   *   Returns this report object for chaining.
   */
  public function setRecipient($recipient);

  /**
   * Gets the normalized delivery status of the message.
   *
   * @return int
   *   The status code which matches the codes used for HTTP.
   */
  public function getStatus();

  /**
   * Sets the normalized delivery status of the message.
   *
   * @param int $status
   *   The status code which matches the codes used for HTTP.
   *
   * @return $this
   *   Returns this report object for chaining.
   */
  public function setStatus($status);

  /**
   * Gets the original delivery status as known to the SMS gateway.
   *
   * @return string
   */
  public function getGatewayStatus();

  /**
   * Sets the original delivery status as known to the SMS gateway.
   *
   * @param string $status
   *
   * @return $this
   *   Returns this report object for chaining.
   */
  public function setGatewayStatus($status);

  /**
   * Gets the time the message was sent.
   *
   * @return int
   *   The UNIX timestamp when the message was sent.
   */
  public function getTimeSent();

  /**
   * Sets the time the message was sent.
   *
   * @param int $time
   *   The UNIX timestamp when the message was sent.
   *
   * @return $this
   *   Returns this report object for chaining.
   */
  public function setTimeSent($time);

  /**
   * Gets the time the message was delivered.
   *
   * @return int
   *   The UNIX timestamp when the message was delivered.
   */
  public function getTimeDelivered();

  /**
   * Sets the time the message was delivered.
   *
   * @param int $time
   *   The UNIX timestamp when the message was delivered.
   *
   * @return $this
   *   Returns this report object for chaining.
   */
  public function setTimeDelivered($time);

  /**
   * Gets the standardized error code in the event there is an error.
   *
   * If there is no error, this method returns 0.
   *
   * @return int
   *
   * @see \Drupal\sms\Message\SmsMessageInterface for the constants.
   */
  public function getError();

  /**
   * Sets the standardized error code in the event there is an error.
   *
   * If there is no error, this method returns 0.
   *
   * @param int $error
   *
   * @return $this
   *   Returns this report object for chaining.
   *
   * @see \Drupal\sms\Message\SmsMessageInterface for the constants.
   *
   */
  public function setError($error);

  /**
   * Gets the standardized error message in the event there is an error.
   *
   * If there is no error, this method returns an empty string.
   *
   * @return string
   *
   * @see \Drupal\sms\Message\SmsMessageInterface for the constants.
   */
  public function getErrorMessage();

  /**
   * Sets the standardized error message in the event there is an error.
   *
   * If there is no error, this method returns an empty string.
   *
   * @param string $error_message
   *
   * @return $this
   *   Returns this report object for chaining.
   *
   * @see \Drupal\sms\Message\SmsMessageInterface for the constants.
   */
  public function setErrorMessage($error_message);

  /**
   * Gets the gateway error code in the event there is an error.
   *
   * If there is no error, this method returns an empty string. These values are
   * gateway dependent and would likely differ across different gateways.
   *
   * @return string
   */
  public function getGatewayError();

  /**
   * Sets the gateway error code in the event there is an error.
   *
   * If there is no error, this method returns an empty string. These values are
   * gateway dependent and would likely differ across different gateways.
   *
   * @param string
   *
   * @return $this
   *   Returns this report object for chaining.
   */
  public function setGatewayError($error);

  /**
   * Gets the gateway error message in the event there is an error.
   *
   * If there is no error, this method returns and empty string. These strings
   * are gateway dependent and would likely differ across different gateways.
   *
   * @return string
   */
  public function getGatewayErrorMessage();

  /**
   * Sets the gateway error message in the event there is an error.
   *
   * If there is no error, this method returns and empty string. These strings
   * are gateway dependent and would likely differ across different gateways.
   *
   * @param string $error_message
   *
   * @return $this
   *   Returns this report object for chaining.
   */
  public function setGatewayErrorMessage($error_message);

}
