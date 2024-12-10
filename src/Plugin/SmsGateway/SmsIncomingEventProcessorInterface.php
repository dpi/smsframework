<?php

declare(strict_types=1);

namespace Drupal\sms\Plugin\SmsGateway;

use Drupal\sms\Event\SmsMessageEvent;

/**
 * Interface for gateways implementing an incoming event subscriber.
 */
interface SmsIncomingEventProcessorInterface {

  /**
   * Process a SMS message from this gateway.
   */
  public function incomingEvent(SmsMessageEvent $event): void;

}
