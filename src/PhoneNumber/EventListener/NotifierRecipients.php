<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumber\EventListener;

use Drupal\notifier\Recipients\NotifierRecipientsInterface;
use Drupal\notifier\Recipients\RecipientQuery;
use Drupal\sms\PhoneNumber\Event\ObjectPhoneNumbers;
use Drupal\sms\PhoneNumber\PhoneNumber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Notifier\Recipient\SmsRecipientInterface;

/**
 * Gets phone numbers via Notifier recipients.
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

  public function onObjectPhoneNumbers(ObjectPhoneNumbers $event): void {
    $recipientQuery = RecipientQuery::defaults()->setOptions($event->options);
    foreach ($this->notifierRecipients->getRecipients($event->for, $recipientQuery) as $recipient) {
      if ($recipient instanceof SmsRecipientInterface) {
        $phoneNumber = $recipient->getPhone();
        if ($phoneNumber !== '') {
          $event->addPhoneNumber(PhoneNumber::create($phoneNumber));
        }
      }
    }
  }

  public static function getSubscribedEvents(): array {
    return [ObjectPhoneNumbers::class => 'onObjectPhoneNumbers'];
  }

}
