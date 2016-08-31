<?php

namespace Drupal\sms\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event fired after a SMS message has been processed.
 *
 * @see \Drupal\sms\Event\SmsEvents
 */
class SmsMessageProcessedEvent extends Event {

  /**
   * The SMS results.
   *
   * @var \Drupal\sms\Message\SmsMessageResultInterface[]
   */
  protected $results;

  /**
   * Set the results for this event.
   *
   * @return \Drupal\sms\Message\SmsMessageResultInterface[]
   *   The results for this event.
   */
  public function getResults() {
    return $this->results;
  }

  /**
   * Sets the results on this event.
   *
   * @param \Drupal\sms\Message\SmsMessageResultInterface[] $results
   *   The results to set on this event.
   *
   * @return $this
   *   Returns this event for chaining.
   */
  public function setResults(array $results) {
    $this->results = $results;
    return $this;
  }

}
