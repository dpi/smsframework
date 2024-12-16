<?php

declare(strict_types=1);

namespace Drupal\sms_test\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\Event\RecipientGatewayEvent;
use Drupal\sms\Event\SmsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Test event subscriber.
 */
final class SmsTestEventSubscriber implements EventSubscriberInterface {

  public const GATEWAY_TEST_200 = 'test_gateway_200';
  public const GATEWAY_TEST_400 = 'test_gateway_400';

  public function __construct(
    private readonly StateInterface $state,
  ) {
  }

  /**
   * Adds a gateway with ID 'test_gateway_200', with weight '200'.
   *
   * @param \Drupal\sms\Event\RecipientGatewayEvent $event
   *   The RecipientGatewayEvent event.
   */
  public function testAddGateway200(RecipientGatewayEvent $event): void {
    if ($this->state->get('sms_test_event_subscriber__test_gateway_200') === TRUE) {
      $gateway = SmsGateway::load(SmsTestEventSubscriber::GATEWAY_TEST_200) ?? throw new \LogicException('Missing test gateway');
      $event->addGateway($gateway, 200);
    }
  }

  /**
   * Adds a gateway with ID 'test_gateway_400', with weight '400'.
   *
   * @param \Drupal\sms\Event\RecipientGatewayEvent $event
   *   The RecipientGatewayEvent event.
   */
  public function testAddGateway400(RecipientGatewayEvent $event): void {
    if ($this->state->get('sms_test_event_subscriber__test_gateway_400') === TRUE) {
      $gateway = SmsGateway::load(SmsTestEventSubscriber::GATEWAY_TEST_400) ?? throw new \LogicException('Missing test gateway');
      $event->addGateway($gateway, 400);
    }
  }

  /**
   * Adds event name to execution order when a message is processed.
   *
   * @param \Symfony\Contracts\EventDispatcher\Event $event
   *   The event.
   * @param string $eventName
   *   The event name.
   */
  public function testExecutionOrder(Event $event, $eventName): void {
    /** @var array $execution_order */
    $execution_order = $this->state->get('sms_test_event_subscriber__execution_order', []);
    $execution_order[] = $eventName;
    $this->state->set('sms_test_event_subscriber__execution_order', $execution_order);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[SmsEvents::MESSAGE_GATEWAY][] = ['testAddGateway200'];
    $events[SmsEvents::MESSAGE_GATEWAY][] = ['testAddGateway400'];

    $events[SmsEvents::MESSAGE_PRE_PROCESS][] = ['testExecutionOrder'];
    $events[SmsEvents::MESSAGE_POST_PROCESS][] = ['testExecutionOrder'];
    $events[SmsEvents::MESSAGE_QUEUE_PRE_PROCESS][] = ['testExecutionOrder'];
    $events[SmsEvents::MESSAGE_QUEUE_POST_PROCESS][] = ['testExecutionOrder'];
    $events[SmsEvents::MESSAGE_INCOMING_PRE_PROCESS][] = ['testExecutionOrder'];
    $events[SmsEvents::MESSAGE_INCOMING_POST_PROCESS][] = ['testExecutionOrder'];
    $events[SmsEvents::MESSAGE_OUTGOING_PRE_PROCESS][] = ['testExecutionOrder'];
    $events[SmsEvents::MESSAGE_OUTGOING_POST_PROCESS][] = ['testExecutionOrder'];

    return $events;
  }

}
