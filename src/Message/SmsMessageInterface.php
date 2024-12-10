<?php

declare(strict_types=1);

namespace Drupal\sms\Message;

use Drupal\sms\Entity\SmsGatewayInterface;

/**
 * Contains information about an SMS message.
 */
interface SmsMessageInterface {

  /**
   * Gets the list of recipients of this SMS message.
   *
   * @return array
   *   The list of recipients of this SMS message.
   */
  public function getRecipients(): array;

  /**
   * Adds a single recipient to the SMS message.
   *
   * @param string $recipient
   *   The recipient to add.
   *
   * @return $this
   *   The called SMS message object.
   */
  public function addRecipient(string $recipient);

  /**
   * Adds multiple recipients to the SMS message.
   *
   * @param string[] $recipients
   *   An array of recipients to add.
   *
   * @return $this
   *   The called SMS message object.
   */
  public function addRecipients(array $recipients);

  /**
   * Removes a single recipient from the SMS message.
   *
   * @param string $recipient
   *   The recipient to remove.
   *
   * @return $this
   *   The called SMS message object.
   */
  public function removeRecipient($recipient);

  /**
   * Removes multiple recipients from the SMS message.
   *
   * @param array $recipients
   *   An array of recipients to remove.
   *
   * @return $this
   *   The called SMS message object.
   */
  public function removeRecipients(array $recipients);

  /**
   * Get the gateway for this message.
   *
   * @return \Drupal\sms\Entity\SmsGatewayInterface|null
   *   A gateway plugin instance, or NULL to let the provider decide.
   */
  public function getGateway(): ?SmsGatewayInterface;

  /**
   * Set the gateway for this message.
   *
   * @param \Drupal\sms\Entity\SmsGatewayInterface $gateway
   *   A gateway plugin instance.
   *
   * @return $this
   *   Return SMS message for chaining.
   */
  public function setGateway(SmsGatewayInterface $gateway);

  /**
   * Get direction of the message.
   *
   * @phpstan-return \Drupal\sms\Direction::*|null
   */
  public function getDirection(): ?int;

  /**
   * Set direction of the message.
   *
   * @param \Drupal\sms\Direction::* $direction
   *   Any of \Drupal\sms\Direction constants.
   *
   * @return $this
   *   Return SMS message for chaining.
   */
  public function setDirection(int $direction);

  /**
   * Gets the options for building or sending this SMS message.
   *
   * @return array
   *   The options for building or sending this SMS message.
   */
  public function getOptions(): array;

  /**
   * Gets the option specified by the key $name.
   *
   * @param string $name
   *   The name of the option.
   *
   * @return mixed
   *   Get the value of the option.
   */
  public function getOption($name): mixed;

  /**
   * Sets an option for this SMS message.
   *
   * @param string $name
   *   The name of the option.
   * @param mixed $value
   *   The value of the option.
   *
   * @return $this
   *   The called SMS message object.
   */
  public function setOption($name, $value);

  /**
   * Removes an option from this SMS message.
   *
   * @param string $name
   *   The name of the option.
   *
   * @return $this
   *   The called SMS message object.
   */
  public function removeOption($name);

  /**
   * Get the result associated with this SMS message.
   *
   * @return \Drupal\sms\Message\SmsMessageResultInterface|null
   *   The result associated with this SMS message, or NULL if there is no
   *   result.
   */
  public function getResult(): ?SmsMessageResultInterface;

  /**
   * Set the result associated with this SMS message.
   *
   * @param \Drupal\sms\Message\SmsMessageResultInterface|null $result
   *   The result to associate with this SMS message, or NULL if there is no
   *   result.
   *
   * @return $this
   *   The called SMS message object.
   */
  public function setResult(?SmsMessageResultInterface $result = NULL);

  /**
   * Gets the name of the sender of this SMS message.
   *
   * @return string|null
   *   The name of the sender, or NULL if none is defined.
   */
  public function getSender(): ?string;

  /**
   * Set the name of the sender of this SMS message.
   *
   * @param string|null $sender
   *   The name of the sender.
   *
   * @return $this
   *   The called SMS message object.
   */
  public function setSender(?string $sender);

  /**
   * Get phone number of the sender.
   */
  public function getSenderNumber(): ?string;

  /**
   * Set the phone number of the sender.
   *
   * @param string $number
   *   The phone number of the sender.
   *
   * @return $this
   *   Return SMS message for chaining.
   */
  public function setSenderNumber(?string $number);

  /**
   * Gets the message to be sent.
   *
   * @return string
   *   The message to be sent.
   */
  public function getMessage(): string;

  /**
   * Set the message to be sent.
   *
   * @param string $message
   *   The message to be sent.
   *
   * @return $this
   *   The called SMS message object.
   */
  public function setMessage(string $message);

  /**
   * Gets the UUID of the SMS object.
   *
   * @return string
   *   The UUID of the SMS object.
   */
  public function getUuid(): string;

  /**
   * Gets the user who created the SMS message.
   *
   * @return positive-int|null
   *   The ID of the user who created the message. Or NULL if no user entity is
   *   associated as the sender.
   */
  public function getUid(): ?int;

  /**
   * Set the user who created the SMS message.
   *
   * @param positive-int|null $uid
   *   The ID of a user entity.
   *
   * @return $this
   *   The called SMS message object.
   */
  public function setUid(?int $uid);

  /**
   * Sets whether this SMS message was generated automatically.
   *
   * @param bool $automated
   *   Whether this SMS message was generated automatically. Set to FALSE if the
   *   message is created due to direct action.
   *
   * @return $this
   *   The called SMS message object.
   */
  public function setAutomated(bool $automated);

  /**
   * Gets whether this SMS message was generated automatically.
   *
   * @return bool
   *   Whether this SMS message was generated automatically.
   */
  public function isAutomated(): bool;

  /**
   * Split this SMS message into new messages by chunks of recipients.
   *
   * @param int $size
   *   The quantity of recipients to chunk by.
   *
   * @return static[]
   *   An array of SMS messages split by recipient chunks.
   */
  public function chunkByRecipients($size): array;

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
   * @see SmsMessageInterface::getReports()
   */
  public function getReport($recipient): ?SmsDeliveryReportInterface;

  /**
   * Gets the delivery reports for all recipients.
   *
   * @return list<\Drupal\sms\Message\SmsDeliveryReportInterface>
   *   An array of delivery reports.
   */
  public function getReports(): array;

}
