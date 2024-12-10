<?php

declare(strict_types=1);

namespace Drupal\sms_test_gateway\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Drupal\sms\Event\SmsEvents;
use Drupal\sms\Event\SmsMessageEvent;
use Drupal\sms\Message\SmsMessageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Test event subscriber.
 */
final class SmsTestGatewayEventSubscriber implements EventSubscriberInterface {

  public const STATE_SMS_INCOMING_PREPROCESS = 'sms_test_gateway_sms_incoming_preprocess';

  public const STATE_MEMORY_INCOMING = 'sms_test_gateway.memory.incoming';

  public function __construct(
    private readonly StateInterface $state,
  ) {
  }

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
    /** @var array<string, mixed> $result */
    $result = &\drupal_static(static::STATE_SMS_INCOMING_PREPROCESS);

    if (!\is_null($sms_message->getRecipients()) && !\is_null($sms_message->getMessage())) {
      $recipients = $sms_message->getRecipients();
      $result['number'] = $recipients !== [] ? \reset($recipients) : NULL;
      $result['message'] = $sms_message->getMessage();
      $this->state->set(static::STATE_SMS_INCOMING_PREPROCESS, $result);
    }

    /** @var array<string, array<\Drupal\sms\Message\SmsMessageInterface>> $incoming_messages */
    $incoming_messages = &\drupal_static(static::STATE_MEMORY_INCOMING, []);
    foreach ($event->getMessages() as $message) {
      \assert($message instanceof SmsMessageInterface);
      $incoming_messages[$message->getGateway()->id()][] = $message;
    }
    $this->state->set(static::STATE_MEMORY_INCOMING, $incoming_messages);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[SmsEvents::MESSAGE_INCOMING_POST_PROCESS][] = ['memoryIncomingMessage'];
    return $events;
  }

}
