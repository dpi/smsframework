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
  protected $is_automated = TRUE;

  /**
   * Creates a new instance of an SMS message.
   *
   * @param string $sender
   *   The sender of the message.
   * @param array $recipients
   *   The list of recipient phone numbers for the message.
   * @param string $message
   *   The actual SMS message to be sent.
   * @param array $options
   *   Additional options to be considered in building the SMS message
   * @param int $uid
   *   The user who created the SMS message.
   */
  public function __construct($sender, array $recipients, $message, array $options, $uid) {
    $this->sender = $sender;
    $this->recipients = $recipients;
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
  public function getMessage() {
    return $this->message;
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
  public function setIsAutomated($is_automated) {
    $this->is_automated = $is_automated;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIsAutomated() {
    return $this->is_automated;
  }

  /**
   * Gets the UUID generator.
   *
   * @return \Drupal\Component\Uuid\UuidInterface
   */
  protected function uuidGenerator() {
    return \Drupal::service('uuid');
  }

}
