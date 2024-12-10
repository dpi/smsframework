<?php

declare(strict_types=1);

namespace Drupal\sms\Provider;

use Drupal\Core\Entity\EntityInterface;
use Drupal\sms\Direction;
use Drupal\sms\Entity\SmsMessage as SmsMessageEntity;
use Drupal\sms\Event\SmsEntityPhoneNumber;
use Drupal\sms\Event\SmsEvents;
use Drupal\sms\Exception\NoPhoneNumberException;
use Drupal\sms\Message\SmsMessageInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Phone number provider.
 */
class PhoneNumberProvider implements PhoneNumberProviderInterface {

  /**
   * Constructs a new PhoneNumberProvider object.
   */
  public function __construct(
    private readonly EventDispatcherInterface $eventDispatcher,
    private readonly SmsProviderInterface $smsProvider,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getPhoneNumbers(EntityInterface $entity, $verified = TRUE): array {
    $event = new SmsEntityPhoneNumber($entity, $verified);
    /** @var \Drupal\sms\Event\SmsEntityPhoneNumber $event */
    $event = $this->eventDispatcher
      ->dispatch($event, SmsEvents::ENTITY_PHONE_NUMBERS);
    return $event->getPhoneNumbers();
  }

  public function sendMessage(EntityInterface $entity, SmsMessageInterface $sms_message): void {
    $phoneNumbers = $this->getPhoneNumbers($entity);
    if ($phoneNumbers === []) {
      throw new NoPhoneNumberException('Attempted to send an SMS to entity without a phone number.');
    }

    $sms_message = SmsMessageEntity::convertFromSmsMessage($sms_message)
      ->addRecipient(\reset($phoneNumbers))
      ->setRecipientEntity($entity)
      ->setDirection(Direction::OUTGOING);

    $this->smsProvider
      ->queue($sms_message);
  }

}
