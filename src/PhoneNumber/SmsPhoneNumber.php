<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\sms\PhoneNumber\Event\ObjectPhoneNumbers;
use Drupal\sms\PhoneNumber\Exception\NoPhoneNumberException;
use Drupal\sms\PhoneNumberVerification\Object\ObjectWithPhoneNumberInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;

/**
 * Service to send SMS to entities.
 *
 * This service is a convenience for when code needs to simply send messages to
 * an entity, and your code would rather not concern itself with where phone
 * numbers are stored or located for an entity.
 *
 * Alternatively, if you want more control. You can construct messages (aka,
 * notifications), recipients, and send the message via Notifier service.
 * See the README. This is what this service does under the hood.
 */
final class SmsPhoneNumber implements SmsPhoneNumberInterface {

  /**
   * @internal
   */
  public function __construct(
    private EventDispatcherInterface $eventDispatcher,
    private NotifierInterface $notifier,
  ) {
  }

  public function send(
    ObjectWithPhoneNumberInterface|EntityInterface $for,
    Notification $notification,
    ?QueryOptions $options = NULL,
  ): void {
    $phoneNumbers = \iterator_to_array($this->getPhoneNumbers($for, $options));
    if ([] === $phoneNumbers) {
      throw new NoPhoneNumberException();
    }

    $this->notifier->send($notification, RecipientProxy::createFromPhoneNumber($phoneNumbers[\array_key_first($phoneNumbers)]));
  }

  public function getPhoneNumbers(
    ObjectWithPhoneNumberInterface|EntityInterface $for,
    ?QueryOptions $options = NULL,
  ): iterable {
    $options ??= QueryOptions::defaults();
    $this->eventDispatcher->dispatch($event = new ObjectPhoneNumbers($for, $options));
    return $event->getPhoneNumbers();
  }

}
