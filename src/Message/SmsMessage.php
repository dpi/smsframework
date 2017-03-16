<?php

namespace Drupal\sms\Message;

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
  protected $uuid;

  /**
   * The sender's name.
   *
   * @var string|NULL
   */
  protected $senderName;

  /**
   * The senders' phone number.
   *
   * @var string
   */
  protected $senderPhoneNumber;

  /**
   * The recipients of the message.
   *
   * @var array
   */
  protected $recipients = [];

  /**
   * The content of the message to be sent.
   *
   * @var string
   */
  protected $message;

  /**
   * The gateway for this message.
   *
   * @var \Drupal\sms\Entity\SmsGatewayInterface
   */
  protected $gateway;

  /**
   * The direction of the message.
   *
   * See \Drupal\sms\Direction constants for potential values.
   *
   * @see \Drupal\sms\Direction
   *
   * @var int
   */
  protected $direction;

  /**
   * @var string
   *   Other options to be used for the sms.
   */
  protected $options = [];

  /**
   * The result associated with this SMS message.
   *
   * @var \Drupal\sms\Message\SmsMessageResultInterface|NULL
   */
  protected $result;

  /**
   * The UID of the creator of the SMS message.
   *
   * @var int
   */
  protected $uid;

  /**
   * Whether this message was generated automatically.
   *
   * @var bool
   */
  protected $automated = TRUE;

  /**
   * Creates a new instance of an SMS message.
   *
   * @param string $sender_phone_number
   *   (optional) The senders' phone number.
   * @param array $recipients
   *   (optional) The list of recipient phone numbers for the message.
   * @param string $message
   *   (optional) The actual SMS message to be sent.
   * @param array $options
   *   (optional) Additional options.
   * @param int $uid
   *   (optional) The user who created the SMS message.
   */
  public function __construct($sender_phone_number = NULL, array $recipients = [], $message = '', array $options = [], $uid = NULL) {
    $this->setSenderNumber($sender_phone_number);
    $this->addRecipients($recipients);
    $this->setMessage($message);
    $this->message = $message;
    $this->options = $options;
    $this->setUid($uid);
    $this->uuid = $this->uuidGenerator()->generate();
  }

  /**
   * {@inheritdoc}
   */
  public function getSender() {
    return $this->senderName;
  }

  /**
   * {@inheritdoc}
   */
  public function setSender($sender) {
    $this->senderName = $sender;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSenderNumber() {
    return $this->senderPhoneNumber;
  }

  /**
   * {@inheritdoc}
   */
  public function setSenderNumber($number) {
    $this->senderPhoneNumber = $number;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
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
  public function getRecipients() {
    return $this->recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function addRecipient($recipient) {
    if (!in_array($recipient, $this->recipients)) {
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
    $this->recipients = array_values(array_diff($this->recipients, [$recipient]));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeRecipients(array $recipients) {
    $this->recipients = array_values(array_diff($this->recipients, $recipients));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGateway() {
    return $this->gateway;
  }

  /**
   * {@inheritdoc}
   */
  public function setGateway(SmsGatewayInterface $gateway) {
    $this->gateway = $gateway;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDirection() {
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
  public function getOptions() {
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($name) {
    if (array_key_exists($name, $this->options)) {
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

  /**
   * {@inheritdoc}
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * {@inheritdoc}
   */
  public function setResult(SmsMessageResultInterface $result = NULL) {
    $this->result = $result;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function getUid() {
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

  /**
   * {@inheritdoc}
   */
  public function isAutomated() {
    return $this->automated;
  }

  /**
   * Gets the UUID generator.
   *
   * @return \Drupal\Component\Uuid\UuidInterface
   *   The UUID generator.
   */
  protected function uuidGenerator() {
    return \Drupal::service('uuid');
  }

  /**
   * {@inheritdoc}
   */
  public function chunkByRecipients($size) {
    $recipients_all = $this->getRecipients();

    // Save processing by returning early.
    if ($size < 1 || count($recipients_all) <= $size) {
      return [$this];
    }

    $base = clone $this;
    $base->removeRecipients($recipients_all);

    $messages = [];
    foreach (array_chunk($recipients_all, $size) as $recipients) {
      $message = clone $base;
      $messages[] = $message->addRecipients($recipients);
    }
    return $messages;
  }

  /**
   * {@inheritdoc}
   */
  public function getReport($recipient) {
    return $this->result ? $this->result->getReport($recipient) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getReports() {
    return $this->result ? $this->result->getReports() : [];
  }

}
