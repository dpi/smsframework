<?php

namespace Drupal\sms\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event fired SMS messages should be processed.
 *
 * @see \Drupal\sms\Event\SmsEvents
 */
class SmsMessageEvent extends Event {

  /**
   * The SMS messages.
   *
   * @var \Drupal\sms\Message\SmsMessageInterface[]
   */
  protected $messages;

  /**
   * Constructs the object.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface[] $messages
   *   The SMS message.
   */
  public function __construct(array $messages) {
    $this->setMessages($messages);
  }

  /**
   * Get all messages on this event.
   *
   * @return \Drupal\sms\Message\SmsMessageInterface[]
   *   The messages on this event.
   */
  public function getMessages() {
    return $this->messages;
  }

  /**
   * Set the messages on this event.
   *
   * @param $messages
   *   The messages to set on this event.
   *
   * @return $this
   *   Returns this event for chaining.
   */
  public function setMessages($messages) {
    $this->messages = $messages;
    return $this;
  }

}
