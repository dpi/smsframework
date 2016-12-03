<?php

namespace Drupal\sms_test_gateway\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\sms\Event\SmsEvents;
use Drupal\sms\Event\SmsMessageEvent;

/**
 * Test event subscriber.
 */
class SmsTestGatewayEventSubscriber implements EventSubscriberInterface {

  /**
   * Store incoming messages in memory.
   *
   * @param \Drupal\sms\Event\SmsMessageEvent $event
   *   The event.
   *
   * @see sms_test_gateway_get_incoming()
   */
  public function memoryIncomingMessage(SmsMessageEvent $event) {
    $sms_message = $event->getMessages()[0];

    // Save incoming result for later retrieval.
    $key = 'sms_test_gateway_sms_incoming_preprocess';
    $result = &drupal_static($key);

    if (!is_null($sms_message->getRecipients()) && !is_null($sms_message->getMessage())) {
      $result['number'] = $sms_message->getRecipients()[0];
      $result['message'] = $sms_message->getMessage();
      \Drupal::state()->set($key, $result);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SmsEvents::MESSAGE_INCOMING_POST_PROCESS][] = ['memoryIncomingMessage'];
    return $events;
  }

}
