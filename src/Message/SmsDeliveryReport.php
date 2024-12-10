<?php

declare(strict_types=1);

namespace Drupal\sms\Message;

/**
 * A value object that holds the SMS delivery report.
 */
class SmsDeliveryReport implements SmsDeliveryReportInterface {

  /**
   * The unique identifier for the message assigned by the gateway.
   */
  protected string $messageId = '';

  /**
   * The recipient of the message.
   */
  protected string $recipient = '';

  /**
   * Status code for the message.
   *
   * @phpstan-var \Drupal\sms\Message\SmsMessageReportStatus::*|null
   */
  protected ?string $status = NULL;

  /**
   * The status message as provided by the gateway API.
   */
  protected string $statusMessage = '';

  /**
   * The timestamp when the delivery report status was updated.
   */
  protected ?int $statusTime = NULL;

  /**
   * The timestamp when the message was queued, or NULL if unknown.
   */
  protected ?int $timeQueued = NULL;

  /**
   * The timestamp when the message was delivered, or NULL if unknown.
   */
  protected ?int $timeDelivered = NULL;

  public function getMessageId(): ?string {
    return $this->messageId;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessageId(?string $message_id) {
    $this->messageId = $message_id;
    return $this;
  }

  public function getRecipient(): string {
    return $this->recipient;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecipient($recipient) {
    $this->recipient = $recipient;
    return $this;
  }

  public function getStatus(): ?string {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->status = $status;
    return $this;
  }

  public function getStatusMessage(): string {
    return $this->statusMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatusMessage(string $message) {
    $this->statusMessage = $message;
    return $this;
  }

  public function getTimeQueued(): ?int {
    return $this->timeQueued;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimeQueued(?int $time) {
    $this->timeQueued = $time;
    return $this;
  }

  public function getTimeDelivered(): ?int {
    return $this->timeDelivered;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimeDelivered(?int $time) {
    $this->timeDelivered = $time;
    return $this;
  }

  public function getStatusTime(): ?int {
    return $this->statusTime;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatusTime(?int $time) {
    $this->statusTime = $time;
    return $this;
  }

}
