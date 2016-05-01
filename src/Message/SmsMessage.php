<?php

/**
 * @file
 * Contains \Drupal\sms\Message\SmsMessage.
 */

namespace Drupal\sms\Message;

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
   * The sender of the message.
   *
   * @var string
   */
  protected $sender;

  /**
   * @var array
   *   The recipients of the message.
   */
  protected $recipients = array();

  /**
   * @var string
   *   The content of the message to be sent.
   */
  protected $message;

  /**
   * @var string
   *   Other options to be used for the sms.
   */
  protected $options = array();

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
   * @param string $sender
   *   (optional) The sender of the message.
   * @param array $recipients
   *   (optional) The list of recipient phone numbers for the message.
   * @param string $message
   *   (optional) The actual SMS message to be sent.
   * @param array $options
   *   (optional) Additional options to be considered in building the SMS message
   * @param int $uid
   *   (optional) The user who created the SMS message.
   */
  public function __construct($sender = '', array $recipients = [], $message = '', array $options = [], $uid = NULL) {
    $this->sender = $sender;
    $this->addRecipients($recipients);
    $this->message = $message;
    $this->options = $options;
    $this->uid = $uid;
    $this->uuid = $this->uuidGenerator()->generate();
  }

  /**
   * {@inheritdoc}
   */
  public function getSender() {
    return $this->sender;
  }

  /**
   * {@inheritdoc}
   */
  public function setSender($sender) {
    $this->sender = $sender;
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

}
