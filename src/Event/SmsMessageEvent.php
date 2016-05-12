<?php

namespace Drupal\sms\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event fired when a new SMS message is being processed before or after send or
 * queue.
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
   * @return \Drupal\sms\Message\SmsMessageInterface[]
   */
  public function getMessages() {
    return $this->messages;
  }

  /**
   * @param $messages
   *
   * @return $this
   */
  public function setMessages($messages) {
    $this->messages = $messages;
    return $this;
  }

}
