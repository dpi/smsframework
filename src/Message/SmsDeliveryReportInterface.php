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

  // Delivery report status codes
  // 0=Unknown, 2xx=Positive, 3xx=Positive/Neutral (context-dependent), 4xx=Negative

  /**
   * Status UNKNOWN.
   *
   * A message would have this status to indicate unknown status.
   */
  const STATUS_UNKNOWN = 0;

  /**
   * Status SENT.
   *
   * A message with this status indicates it was successfully sent.
   */
  const STATUS_SENT = 200;

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
  const STATUS_QUEUED = 300;

  /**
   * Status PENDING.
   *
   * A message with this status indicates it is pending delivery.
   */
  const STATUS_PENDING = 302;

  /**
   * Status ERROR.
   *
   * A message with this status indicates it could not be sent (routing reasons).
   */
  const STATUS_ERROR = 400;

  /**
   * Status NO_CREDIT.
   *
   * A message with this status indicates it was not sent due to low credit.
   */
  const STATUS_NO_CREDIT = 402;

  /**
   * Status NOT_SENT.
   *
   * A message with this status indicates it was not sent.
   */
  const STATUS_NOT_SENT = 404;

  /**
   * Status NOT_DELIVERED.
   *
   * A message with this status indicates it was sent but not delivered.
   */
  const STATUS_NOT_DELIVERED = 406;

  /**
   * Status EXPIRED.
   *
   * A message with this status indicates it was sent but expired in the SMSC.
   */
  const STATUS_EXPIRED = 408;

  /**
   * Status REJECTED.
   *
   * A message with this status indicates it was rejected by the network.
   */
  const STATUS_REJECTED = 410;

  /**
   * Status INVALID_RECIPIENT.
   *
   * A message with this status indicates the recipient number was invalid.
   */
  const STATUS_INVALID_RECIPIENT = 412;

  /**
   * Status INVALID_SENDER.
   *
   * A message with this status indicates the sender ID was invalid.
   */
  const STATUS_INVALID_SENDER = 414;

  /**
   * Status ERROR_ROUTING.
   *
   * A message with this status there was a routing error.
   */
  const STATUS_ERROR_ROUTING = 420;

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
   * Gets the recipient for which the message was intended.
   *
   * @return string
   */
  public function getRecipient();

  /**
   * Gets the normalized delivery status of the message.
   *
   * @return int
   *   The status code which matches the codes used for HTTP.
   */
  public function getStatus();

  /**
   * Gets the original delivery status as known to the SMS gateway.
   *
   * @return string
   */
  public function getGatewayStatus();

  /**
   * Gets the time the message was sent.
   *
   * @return int
   *   The UNIX timestamp when the message was sent.
   */
  public function getTimeSent();

  /**
   * Gets the time the message was delivered.
   *
   * @return int
   *   The UNIX timestamp when the message was delivered.
   */
  public function getTimeDelivered();

  /**
   * Returns the delivery report as a keyed array.
   *
   * @return array
   *   An array with the following keys:
   *   - status: the actual delivery status of the message per the STATUS_*
   *     constants in this class.
   *   - message_id: The message id if the message was successfully sent to that
   *       recipient. An empty string means the message was not sent.
   *   - error_code: The error code number for a specific message.
   *   - error_message: The description of the error message.
   *   - gateway_status: The original delivery status from the SMS gateway.
   *   - gateway_error_code: The original error code from the SMS gateway.
   *   - gateway_error_message: The original error message from the SMS gateway.
   * @return mixed
   */
  public function getArray();

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
   * Gets the gateway error code in the event there is an error.
   *
   * If there is no error, this method returns an empty string. These values are
   * gateway dependent and would likely differ across different gateways.
   *
   * @return string
   */
  public function getGatewayError();

  /**
   * Gets the gateway error message in the event there is an error.
   *
   * If there is no error, this method returns and empty string. These strings
   * are gateway dependent and would likely differ across different gateways.
   *
   * @return string
   */
  public function getGatewayErrorMessage();

}
