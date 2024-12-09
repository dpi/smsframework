<?php

declare(strict_types=1);

namespace Drupal\sms_user\EventSubscriber;

use Drupal\sms\Event\SmsEvents;
use Drupal\sms\Event\SmsMessageEvent;
use Drupal\sms_user\AccountRegistrationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber responding to SMS Framework events.
 */
class SmsEventSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new SmsEvents instance.
   */
  final public function __construct(
    private readonly AccountRegistrationInterface $accountRegistration,
  ) {
  }

  /**
   * Process an incoming SMS to see if a new account should be created.
   */
  public function createAccount(SmsMessageEvent $event): void {
    foreach ($event->getMessages() as $sms_message) {
      $this->accountRegistration->createAccount($sms_message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[SmsEvents::MESSAGE_INCOMING_POST_PROCESS][] = ['createAccount'];
    return $events;
  }

}
