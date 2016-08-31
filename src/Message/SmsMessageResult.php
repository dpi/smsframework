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
   * The message delivery reports.
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
  public function getReport($recipient) {
    foreach ($this->reports as $report) {
      if (in_array($report->getRecipients(), $recipient)) {
        return $report;
      }
    }
    return NULL;
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
