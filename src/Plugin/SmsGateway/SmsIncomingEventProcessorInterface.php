<?php

namespace Drupal\sms\Plugin\SmsGateway;

use Drupal\sms\Event\SmsMessageEvent;

/**
 * Interface for gateways implementing an incoming event subscriber.
 */
interface SmsIncomingEventProcessorInterface {

  /**
   * Process a SMS message from this gateway.
   *
   * @param \Drupal\sms\Event\SmsMessageEvent $event
   *   The event.
   */
  public function incomingEvent(SmsMessageEvent $event);

}
