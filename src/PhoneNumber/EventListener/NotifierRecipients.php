<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumber\EventListener;

use Drupal\notifier\Recipients\NotifierRecipientsInterface;
use Drupal\sms\PhoneNumber\Event\EntityPhoneNumbers;
use Drupal\sms\PhoneNumber\PhoneNumber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Notifier\Recipient\SmsRecipientInterface;

/**
 * Gets phone numbers from configured entity fields.
 *
 * See README for configuration details.
 */
final class NotifierRecipients implements EventSubscriberInterface {

  /**
   * @internal
   */
  public function __construct(
    private readonly NotifierRecipientsInterface $notifierRecipients,
  ) {
  }

  public function onEntityPhoneNumbers(EntityPhoneNumbers $event): void {
    foreach ($this->notifierRecipients->getRecipients($event->for) as $recipient) {
      if ($recipient instanceof SmsRecipientInterface) {
        $phoneNumber = $recipient->getPhone();
        if ($phoneNumber !== '') {
          $event->addPhoneNumber(PhoneNumber::create($phoneNumber));
        }
      }
    }
  }

  public static function getSubscribedEvents(): array {
    return [EntityPhoneNumbers::class => 'onEntityPhoneNumbers'];
  }

}
