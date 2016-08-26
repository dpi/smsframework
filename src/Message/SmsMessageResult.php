<?php

/**
 * @file
 * Contains \Drupal\sms\Message\SmsMessageResult
 */

namespace Drupal\sms\Message;

/**
 * The result of an SMS messaging transaction.
 */
class SmsMessageResult implements SmsMessageResultInterface {

  /**
   * The status of the message, or NULL if unknown.
   *
   * @var string|NULL
   */
  public $status = NULL;

  /**
   * The status message as provided by the gateway API.
   *
   * @var string
   */
  protected $statusMessage = '';

  /**
   * The message delivery reports keyed by recipient number.
   *
   * @var \Drupal\sms\Message\SmsDeliveryReportInterface[]
   */
  public $reports = [];

  /**
   * The credit balance after this message is sent, or NULL if unknown.
   *
   * This number is in the SMS gateway's chosen denomination.
   *
   * @var float|NULL
   */
  public $creditBalance = NULL;

  /**
   * The credits consumed to process this message, or NULL if unknown.
   *
   * This number is in the SMS gateway's chosen denomination.
   *
   * @var float|NULL
   */
  public $creditsUsed = NULL;

  /**
   * The messages associated with this result.
   *
   * @var \Drupal\sms\Message\SmsMessageInterface[]
   */
  public $messages = [];

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
  public function getStatusMessage() {
    return $this->statusMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatusMessage($message) {
    $this->statusMessage = $message;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages() {
    return $this->messages;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessages(array $messages) {
    $this->messages = $messages;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getReport($recipient) {
    return isset($this->reports[$recipient]) ? $this->reports[$recipient] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getReports() {
    return $this->reports;
  }

  /**
   * {@inheritdoc}
   */
  public function setReports(array $reports) {
    $this->reports = $reports;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreditsBalance() {
    return $this->creditBalance;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreditsBalance($balance) {
    $this->creditBalance = $balance;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreditsUsed() {
    return $this->creditsUsed;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreditsUsed($credits_used) {
    $this->creditsUsed = $credits_used;
    return $this;
  }

}
