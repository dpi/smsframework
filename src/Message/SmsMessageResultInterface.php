<?php

declare(strict_types=1);

namespace Drupal\sms\Message;

/**
 * Contains information on SMS message results.
 */
interface SmsMessageResultInterface {

  /**
   * Gets the error of the message.
   *
   * @return \Drupal\sms\Message\SmsMessageResultStatus::*|null
   *   An error code, or NULL if there was no error.
   */
  public function getError(): ?string;

  /**
   * Sets the error of the message.
   *
   * Usually a setting an error on a result indicates something went wrong with
   * the entire transaction.
   *
   * @param \Drupal\sms\Message\SmsMessageResultStatus::*|null $error
   *   An error code, or NULL if unknown.
   *
   * @return $this
   */
  public function setError(?string $error);

  /**
   * Gets the error message.
   *
   * @return string
   *   The error message as provided by the gateway API.
   */
  public function getErrorMessage(): string;

  /**
   * Sets the error message.
   *
   * @param string $message
   *   The error message as provided by the gateway API.
   *
   * @return $this
   */
  public function setErrorMessage(string $message);

  /**
   * Gets the delivery report for a particular recipient.
   *
   * @param string $recipient
   *   The number of the recipient for which the report is to be retrieved.
   *
   * @return \Drupal\sms\Message\SmsDeliveryReportInterface|null
   *   A delivery report object, or NULL if there is no report for the
   *   recipient.
   *
   * @see SmsMessageResultInterface::getReports()
   */
  public function getReport(string $recipient): ?SmsDeliveryReportInterface;

  /**
   * Gets the delivery reports for all recipients.
   *
   * @phpstan-return \Drupal\sms\Message\SmsDeliveryReportInterface[]
   */
  public function getReports(): array;

  /**
   * Sets the delivery reports for all recipients.
   *
   * @param \Drupal\sms\Message\SmsDeliveryReportInterface[] $reports
   *   An array of delivery reports.
   *
   * @return $this
   */
  public function setReports(array $reports);

  /**
   * Adds a delivery report to the result.
   *
   * @param \Drupal\sms\Message\SmsDeliveryReportInterface $report
   *   A delivery report.
   *
   * @return $this
   */
  public function addReport(SmsDeliveryReportInterface $report);

  /**
   * Gets the credit balance after this transaction.
   *
   * @return float|null
   *   The credit balance after the message is processed, or NULL if unknown.
   */
  public function getCreditsBalance(): ?float;

  /**
   * Sets the credit balance after this transaction.
   *
   * @param float|null $balance
   *   The credit balance after the message is processed, or NULL if unknown.
   *
   * @return $this
   *
   * @throws \Drupal\sms\Exception\SmsException
   *   Thrown if balance set is an invalid variable type.
   */
  public function setCreditsBalance(?float $balance);

  /**
   * Gets the credits consumed for this transaction.
   *
   * @return float|null
   *   The credits consumed for this transaction, or NULL if unknown.
   */
  public function getCreditsUsed(): ?float;

  /**
   * Sets the credits consumed for this transaction.
   *
   * @param float|null $credits_used
   *   The credits consumed for this transaction, or NULL if unknown.
   *
   * @return $this
   *
   * @throws \Drupal\sms\Exception\SmsException
   *   Thrown if credits set is an invalid variable type.
   */
  public function setCreditsUsed(?float $credits_used);

}
