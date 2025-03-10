<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\sms\PhoneNumberVerification\Object\ObjectWithPhoneNumberInterface;
use Symfony\Component\Notifier\Notification\Notification;

interface SmsPhoneNumberInterface {

  /**
   * Sends a message to an object.
   *
   * Since entities can have more than one phone number, the message will be
   * sent to the first phone number found. You can control the positioning of
   * phone numbers (first, and others) by reacting to the ObjectPhoneNumbers
   * event and modifying the collection value returned by getPhoneNumbers.
   *
   * @throws \Drupal\sms\PhoneNumber\Exception\NoPhoneNumberException
   *   Thrown when the provided object does not have at least one phone number.
   */
  public function send(ObjectWithPhoneNumberInterface|EntityInterface $for, Notification $notification, ?QueryOptions $options = NULL): void;

  /**
   * Retrieves phone numbers for an entity.
   *
   * @phpstan-return iterable<PhoneNumberInterface>
   */
  public function getPhoneNumbers(ObjectWithPhoneNumberInterface|EntityInterface $for, ?QueryOptions $options = NULL): iterable;

}
