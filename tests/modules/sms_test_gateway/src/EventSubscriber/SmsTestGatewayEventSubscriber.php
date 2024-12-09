<?php

declare(strict_types=1);

namespace Drupal\sms_test_gateway\EventSubscriber;

use Drupal\sms\Event\SmsEvents;
use Drupal\sms\Event\SmsMessageEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Test event subscriber.
 */
final class SmsTestGatewayEventSubscriber implements EventSubscriberInterface {

  public const STATE_SMS_INCOMING_PREPROCESS = 'sms_test_gateway_sms_incoming_preprocess';

  public const STATE_MEMORY_INCOMING = 'sms_test_gateway.memory.incoming';

  /**
   * Store incoming messages in memory.
   *
   * @param \Drupal\sms\Event\SmsMessageEvent $event
   *   The event.
   *
   * @see sms_test_gateway_get_incoming()
   */
  public function memoryIncomingMessage(SmsMessageEvent $event): void {
    $sms_message = $event->getMessages()[0];

    // Save incoming result for later retrieval.
    $result = &\drupal_static(static::STATE_SMS_INCOMING_PREPROCESS);

    if (!\is_null($sms_message->getRecipients()) && !\is_null($sms_message->getMessage())) {
      $recipients = $sms_message->getRecipients();
      $result['number'] = \count($recipients) ? \reset($recipients) : NULL;
      $result['message'] = $sms_message->getMessage();
      \Drupal::state()->set(static::STATE_SMS_INCOMING_PREPROCESS, $result);
    }

    $incoming_messages = &\drupal_static(static::STATE_MEMORY_INCOMING, []);
    foreach ($event->getMessages() as $message) {
      /** @var \Drupal\sms\Message\SmsMessageInterface $message */
      $incoming_messages[$message->getGateway()->id()][] = $message;
    }
    \Drupal::state()->set(static::STATE_MEMORY_INCOMING, $incoming_messages);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[SmsEvents::MESSAGE_INCOMING_POST_PROCESS][] = ['memoryIncomingMessage'];
    return $events;
  }

}
