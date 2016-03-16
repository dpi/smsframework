<?php

/**
 * @file
 * Contains \Drupal\sms\Message\SmsDeliveryReport.
 */

namespace Drupal\sms\Message;

/**
 * A value object that holds the SMS delivery report.
 */
class SmsDeliveryReport implements SmsDeliveryReportInterface {

  /**
   * The unique identifier for the message assigned by the gateway.
   *
   * Usually message IDs are returned by the gateway to identify a message sent
   * and should be unique for a particular combination of message and recipient.
   *
   * @var string
   */
  protected $messageId;

  /**
   * The UNIX timestamp when the message was sent.
   *
   * @var string
   */
  protected $timeSent;

  /**
   * The UNIX timestamp when the message was delivered.
   *
   * @var string
   */
  protected $timeDelivered;

  /**
   * The recipient of the message.
   *
   * @var string
   */
  protected $recipient;

  /**
   * The error code sent from the gateway.
   *
   * @var string
   */
  protected $gatewayErrorCode;

  /**
   * The error message sent from the gateway.
   *
   * @var string
   */
  protected $gatewayErrorMessage;

  /**
   * The SMS delivery status sent from the gateway.
   *
   * @var string
   */
  protected $gatewayStatus;

  /**
   * The standardized error code.
   *
   * @var string
   */
  protected $errorCode;

  /**
   * The standardized error message.
   *
   * @var string
   */
  protected $errorMessage;

  /**
   * The standardized SMS delivery status.
   *
   * @var string
   */
  protected $status;

  /**
   * Default values.
   *
   * @var array
   */
  protected static $defaults = [
    'message_id' => '',
    'recipient' => '',
    'status' => self::STATUS_SENT,
    'error_code' => 0,
    'error_message' => '',
    'gateway_status' => 'SENT',
    'gateway_error_code' => '',
    'gateway_error_message' => '',
    'time_sent' => REQUEST_TIME,
    'time_delivered' => REQUEST_TIME,
  ];

  /**
   * Creates a new instance of an SMS delivery report.
   *
   * @param array $data
   *   The data to instantiate the delivery report with. It should have the
   *   following keys.
   *   - message_id: the essage ID from the gateway (default is '')
   *   - recipient: the recipient number (single number, default is '')
   *   - status: the delivery status (default is
   *     SmsDeliveryReportInterface::STATUS_SENT)
   *   - gateway_status: the delivery status from the gateway (default is 'SENT')
   *   - send_time: the time the message was sent (default is REQUEST_TIME)
   *   - delivered_time: the time the message was delivered (default is
   *     REQUEST_TIME)
   */
  public function __construct($data) {
    $data += static::$defaults;
    $this->messageId = $data['message_id'];
    $this->recipient = $data['recipient'];
    $this->timeDelivered = $data['time_delivered'];
    $this->timeSent = $data['time_sent'];
    $this->status = $data['status'];
    $this->errorCode = $data['error_code'];
    $this->errorMessage = $data['error_message'];
    $this->gatewayStatus = $data['gateway_status'];
    $this->gatewayErrorCode = $data['gateway_error_code'];
    $this->gatewayErrorMessage = $data['gateway_error_message'];
  }

  /**
   * {@inheritdoc}
   */
  public function getMessageId() {
    return $this->messageId;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipient() {
    return $this->recipient;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeDelivered() {
    return $this->timeDelivered;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeSent() {
    return $this->timeSent;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function getError() {
    return $this->errorCode;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorMessage() {
    return $this->errorMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function getGatewayStatus() {
    return $this->gatewayStatus;
  }

  /**
   * {@inheritdoc}
   */
  public function getGatewayError() {
    return $this->gatewayErrorCode;
  }

  /**
   * {@inheritdoc}
   */
  public function getGatewayErrorMessage() {
    return $this->gatewayErrorMessage;
  }

  public function getArray() {
    return (array) $this;
  }

}
