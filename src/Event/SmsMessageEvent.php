<?php

declare(strict_types=1);

namespace Drupal\sms\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event fired when SMS messages are processed.
 *
 * This event can be used for both pre- and post- process events. See
 * {@link \Drupal\sms\Event\SmsEvents} to see where this event is used.
 *
 * @see \Drupal\sms\Event\SmsEvents
 *
 * @template-covariant T of \Drupal\sms\Message\SmsMessageInterface=\Drupal\sms\Message\SmsMessageInterface
 */
final class SmsMessageEvent extends Event {

  /**
   * Constructs the object.
   *
   * @param T[] $messages
   *   The SMS message.
   */
  public function __construct(
    protected array $messages,
  ) {
  }

  /**
   * Get all messages on this event.
   *
   * @return T[]
   *   The messages on this event.
   */
  public function getMessages(): array {
    return $this->messages;
  }

  /**
   * Set the messages on this event.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface[] $messages
   *   The messages to set on this event.
   *
   * @return $this
   *   Returns this event for chaining.
   */
  public function setMessages(array $messages) {
    /** @var T[] $messages */
    $this->messages = $messages;
    return $this;
  }

}
