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
   * Creates a new instance of an SMS message.
   *
   * @param string
   *   The sender of the message.
   * @param array
   *   The list of recipient phone numbers for the message.
   * @param string
   *   The actual SMS message to be sent.
   * @param array
   *   Additional options to be considered in building the SMS message
   */
  public function __construct($sender, array $recipients, $message, array $options) {
    $this->sender = $sender;
    $this->recipients = $recipients;
    $this->message = $message;
    $this->options = $options;
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

}
