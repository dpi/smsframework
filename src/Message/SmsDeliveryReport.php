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
   * @var string
   */
  protected $messageId = '';

  /**
   * The UNIX timestamp when the message was sent.
   *
   * @var string|NULL
   */
  protected $timeSent = NULL;

  /**
   * The UNIX timestamp when the message was delivered.
   *
   * @var string|NULL
   */
  protected $timeDelivered = NULL;

  /**
   * The recipient of the message.
   *
   * @var string
   */
  protected $recipient = NULL;

  /**
   * The error code sent from the gateway.
   *
   * @var string
   */
  protected $gatewayErrorCode = NULL;

  /**
   * The error message sent from the gateway.
   *
   * @var string
   */
  protected $gatewayErrorMessage = NULL;

  /**
   * The SMS delivery status sent from the gateway.
   *
   * @var string
   */
  protected $gatewayStatus = NULL;

  /**
   * The standardized error code.
   *
   * @var string
   */
  protected $errorCode = NULL;

  /**
   * The standardized error message.
   *
   * @var string
   */
  protected $errorMessage = NULL;

  /**
   * The standardized SMS delivery status.
   *
   * @var string
   */
  protected $status = NULL;

  /**
   * {@inheritdoc}
   */
  public function getMessageId() {
    return $this->messageId;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessageId($message_id) {
    $this->messageId = $message_id;
    return $this;
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
  public function setRecipient($recipient) {
    $this->recipient = $recipient;
    return $this;
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
  public function setTimeDelivered($time) {
    $this->timeDelivered = $time;
    return $this;
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
  public function setTimeSent($time) {
    $this->timeSent = $time;
    return $this;
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
  public function setStatus($status) {
    $this->status = $status;
    return $this;
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
  public function setError($error) {
    $this->errorCode = $error;
    return $this;
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
  public function setErrorMessage($error_message) {
    $this->errorMessage = $error_message;
    return $this;
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
  public function setGatewayStatus($status) {
    $this->gatewayStatus = $status;
    return $this;
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
  public function setGatewayError($error) {
    $this->gatewayErrorCode = $error;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGatewayErrorMessage() {
    return $this->gatewayErrorMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function setGatewayErrorMessage($error_message) {
    $this->gatewayErrorMessage = $error_message;
    return $this;
  }

}
