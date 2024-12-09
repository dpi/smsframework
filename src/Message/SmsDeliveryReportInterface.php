<?php

declare(strict_types=1);

namespace Drupal\sms\Message;

/**
 * Contains information about an SMS message.
 */
interface SmsDeliveryReportInterface {

  /**
   * Gets the gateway tracking ID for the message.
   *
   * @return string|null
   *   The gateway tracking ID for the message, or NULL if there is no tracking
   *   ID.
   */
  public function getMessageId(): ?string;

  /**
   * Sets the gateway tracking ID for the message.
   *
   * @param string|null $message_id
   *   The gateway tracking ID for the message, or NULL if there is no tracking
   *   ID.
   *
   * @return $this
   */
  public function setMessageId(?string $message_id);

  /**
   * Gets the recipient for the message.
   *
   * @return string
   *   The recipient for the message.
   */
  public function getRecipient(): string;

  /**
   * Sets the recipient for the message.
   *
   * @param string $recipient
   *   The recipient for the message.
   *
   * @return $this
   */
  public function setRecipient(string $recipient);

  /**
   * Get the status of the message. NULL if unknown.
   *
   * @phpstan-return \Drupal\sms\Message\SmsMessageReportStatus::*|null
   */
  public function getStatus(): ?string;

  /**
   * Sets the status of the message. NULL if unknown.
   *
   * @phpstan-param \Drupal\sms\Message\SmsMessageReportStatus::*|null $status
   *
   * @return $this
   */
  public function setStatus(?string $status);

  /**
   * Gets the status message, as provided by the gateway API.
   */
  public function getStatusMessage(): string;

  /**
   * Sets the status message, provided by the gateway API.
   *
   * @return $this
   */
  public function setStatusMessage(string $message);

  /**
   * Get the timestamp when the message was queued. NULL if unknown.
   */
  public function getTimeQueued(): ?int;

  /**
   * Set the timestamp when the message was queued. NULL if unknown.
   *
   * @return $this
   */
  public function setTimeQueued(?int $time);

  /**
   * Gets the time the message was delivered to the recipient. NULL if unknown.
   */
  public function getTimeDelivered(): ?int;

  /**
   * Set the time the message was delivered to the recipient. NULL if unknown.
   *
   * @return $this
   */
  public function setTimeDelivered(?int $time);

  /**
   * Gets the gateway-provided timestamp for the current status.
   */
  public function getStatusTime(): int;

  /**
   * Sets the gateway-provided timestamp for the current status.
   *
   * @return $this
   */
  public function setStatusTime(int $time);

}
