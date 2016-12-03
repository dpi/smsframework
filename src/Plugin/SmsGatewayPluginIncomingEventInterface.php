<?php

namespace Drupal\sms\Plugin;

use Drupal\sms\Event\SmsMessageEvent;

/**
 * Interface for gateways implementing an incoming event subscriber.
 */
interface SmsGatewayPluginIncomingEventInterface {

  /**
   * Process a SMS message from this gateway.
   *
   * @param \Drupal\sms\Event\SmsMessageEvent $event
   *   The event.
   */
  public function incomingEvent(SmsMessageEvent $event);

}
