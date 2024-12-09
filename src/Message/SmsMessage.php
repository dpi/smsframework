<?php

declare(strict_types=1);

namespace Drupal\sms\Message;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\sms\Entity\SmsGatewayInterface;

/**
 * Basic implementation of an SMS message.
 */
class SmsMessage implements SmsMessageInterface {

  /**
   * The unique identifier for this message.
   *
   * @var string
   */
  protected string $uuid;

  /**
   * The sender's name.
   *
   * @var string|null
   */
  protected ?string $senderName = NULL;

  /**
   * The senders' phone number.
   *
   * @var string|null
   */
  protected ?string $senderPhoneNumber;

  /**
   * The recipients of the message.
   *
   * @var string[]
   */
  protected array $recipients = [];

  /**
   * The content of the message to be sent.
   *
   * @var string
   */
  protected string $message;

  /**
   * The gateway for this message.
   *
   * @var \Drupal\sms\Entity\SmsGatewayInterface|null
   */
  protected ?SmsGatewayInterface $gateway = NULL;

  /**
   * The direction of the message.
   *
   * See \Drupal\sms\Direction constants for potential values.
   *
   * Since direction is not a part of the constructor, it needs to be nullable.
   *
   * @var \Drupal\sms\Direction::*|null
   */
  protected ?int $direction = NULL;

  /**
   * Other options to be used for the SMS.
   *
   * @var array<mixed>
   */
  protected array $options = [];

  /**
   * The result associated with this SMS message.
   *
   * @var \Drupal\sms\Message\SmsMessageResultInterface|null
   */
  protected ?SmsMessageResultInterface $result = NULL;

  /**
   * The UID of the creator of the SMS message.
   *
   * @var int|null
   */
  protected ?int $uid = NULL;

  /**
   * Whether this message was generated automatically.
   *
   * @var bool
   */
  protected bool $automated = TRUE;

  /**
   * Creates a new instance of an SMS message.
   *
   * @param string|null $sender_phone_number
   *   (optional) The senders' phone number.
   * @param array $recipients
   *   (optional) The list of recipient phone numbers for the message.
   * @param string $message
   *   (optional) The actual SMS message to be sent.
   * @param array $options
   *   (optional) Additional options.
   * @param int|null $uid
   *   (optional) The user who created the SMS message.
   */
  public function __construct(
    ?string $sender_phone_number = NULL,
    array $recipients = [],
    string $message = '',
    array $options = [],
    ?int $uid = NULL,
  ) {
    $this->setSenderNumber($sender_phone_number);
    $this->addRecipients($recipients);
    $this->setMessage($message);
    $this->options = $options;
    $this->setUid($uid);
    $this->uuid = static::uuidGenerator()->generate();
  }

  public function getSender(): ?string {
    return $this->senderName;
  }

  /**
   * {@inheritdoc}
   */
  public function setSender($sender) {
    $this->senderName = $sender;
    return $this;
  }

  public function getSenderNumber(): string {
    return $this->senderPhoneNumber;
  }

  /**
   * {@inheritdoc}
   */
  public function setSenderNumber($number) {
    $this->senderPhoneNumber = $number;
    return $this;
  }

  public function getMessage(): string {
    return $this->message;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage($message) {
    $this->message = $message;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients(): array {
    return $this->recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function addRecipient($recipient) {
    if (!\in_array($recipient, $this->recipients, TRUE)) {
      $this->recipients[] = $recipient;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addRecipients(array $recipients) {
    foreach ($recipients as $recipient) {
      $this->addRecipient($recipient);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeRecipient($recipient) {
    $this->recipients = \array_values(\array_diff($this->recipients, [$recipient]));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeRecipients(array $recipients) {
    $this->recipients = \array_values(\array_diff($this->recipients, $recipients));
    return $this;
  }

  public function getGateway(): ?SmsGatewayInterface {
    return $this->gateway;
  }

  /**
   * {@inheritdoc}
   */
  public function setGateway(SmsGatewayInterface $gateway) {
    $this->gateway = $gateway;
    return $this;
  }

  public function getDirection(): ?int {
    return $this->direction;
  }

  /**
   * {@inheritdoc}
   */
  public function setDirection($direction) {
    $this->direction = $direction;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($name): mixed {
    if (\array_key_exists($name, $this->options)) {
      return $this->options[$name];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setOption($name, $value) {
    $this->options[$name] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeOption($name) {
    unset($this->options[$name]);
    return $this;
  }

  public function getResult(): ?SmsMessageResultInterface {
    return $this->result;
  }

  /**
   * {@inheritdoc}
   */
  public function setResult(?SmsMessageResultInterface $result = NULL) {
    $this->result = $result;
    return $this;
  }

  public function getUuid(): string {
    return $this->uuid;
  }

  public function getUid(): ?int {
    return $this->uid;
  }

  /**
   * {@inheritdoc}
   */
  public function setUid($uid) {
    $this->uid = $uid;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAutomated($automated) {
    $this->automated = $automated;
    return $this;
  }

  public function isAutomated(): bool {
    return $this->automated;
  }

  protected static function uuidGenerator(): UuidInterface {
    return \Drupal::service(UuidInterface::class);
  }

  /**
   * {@inheritdoc}
   */
  public function chunkByRecipients($size): array {
    $recipients_all = $this->getRecipients();

    // Save processing by returning early.
    if ($size < 1 || \count($recipients_all) <= $size) {
      return [$this];
    }

    $base = clone $this;
    $base->removeRecipients($recipients_all);

    $messages = [];
    foreach (\array_chunk($recipients_all, $size) as $recipients) {
      $message = clone $base;
      $messages[] = $message->addRecipients($recipients);
    }
    return $messages;
  }

  /**
   * {@inheritdoc}
   */
  public function getReport($recipient): ?SmsDeliveryReportInterface {
    return $this->result?->getReport($recipient);
  }

  /**
   * {@inheritdoc}
   */
  public function getReports(): array {
    return $this->result ? $this->result->getReports() : [];
  }

}
