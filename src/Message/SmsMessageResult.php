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
   * @var bool
   */
  public $status;

  /**
   * The translated error message if status is FALSE.
   *
   * @var string
   */
  public $errorMessage;

  /**
   * The credits used for this message.
   *
   * @var integer
   */
  public $creditsUsed;

  /**
   * The credit balance after this message is sent.
   *
   * @var integer
   */
  public $creditBalance;

  /**
   * The message reports keyed by recipient number.
   *
   * @var array
   */
  public $report;

  /**
   * Create a new message result based on data supplied in the array.
   *
   * @param array $data
   *   Information to be used to instantiate the SmsMessageResult.
   */
  public function __construct($data) {
    $data += $this->defaultData();
    $this->status = $data['status'];
    $this->creditBalance = $data['credit_balance'];
    $this->creditsUsed = $data['credits_used'];
    $this->errorMessage = $data['error_message'];
    $this->report = $data['report'];
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
  public function getReport() {
    return $this->report;
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
  public function getCreditsUsed() {
    return $this->creditsUsed;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    return array(
      'status' => $this->status,
      'error_message' => $this->errorMessage,
      'credits_used' => $this->creditsUsed,
      'credit_balance' => $this->creditBalance,
      'report' => $this->report,
    );
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
  public function getReportFor($recipient) {
    if (isset($this->report[$recipient])) {
      return $this->report[$recipient];
    }
    else {
      return null;
    }
  }

  /**
   * Returns default data for initializing the value object.
   */
  protected function defaultData() {
    return array(
      'status' => '',
      'error_message' => '',
      'credits_used' => 0,
      'credit_balance' => 0,
      'report' => array(),
    );
  }

}
