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
   * The status of the message.
   *
   * @var boolean|NULL
   */
  public $status = NULL;

  /**
   * The error message if status was negative.
   *
   * @var string
   */
  public $errorMessage = '';

  /**
   * The credits used for this message.
   *
   * @var integer
   */
  public $creditsUsed = 0;

  /**
   * The credit balance after this message is sent.
   *
   * @var integer
   */
  public $creditBalance = 0;

  /**
   * The message delivery reports keyed by recipient number.
   *
   * @var \Drupal\sms\Message\SmsDeliveryReportInterface[]
   */
  public $reports = [];

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
  public function getErrorMessage() {
    return $this->errorMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function setErrorMessage($error_message) {
    return $this->errorMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function getReport($recipient) {
    if (isset($this->reports[$recipient])) {
      return $this->reports[$recipient];
    }
    else {
      return NULL;
    }
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
  public function getBalance() {
    return $this->creditBalance;
  }

  /**
   * {@inheritdoc}
   */
  public function setBalance($balance) {
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
