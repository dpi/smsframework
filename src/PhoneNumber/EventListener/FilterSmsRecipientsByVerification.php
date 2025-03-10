<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumber\EventListener;

use Drupal\Core\Entity\EntityInterface;
use Drupal\notifier\Recipients\Event\Recipients;
use Drupal\sms\PhoneNumber\PhoneNumber;
use Drupal\sms\PhoneNumber\QueryOptions;
use Drupal\sms\PhoneNumberVerification\Enum\VerificationRequirement;
use Drupal\sms\PhoneNumberVerification\Enum\Verified;
use Drupal\sms\PhoneNumberVerification\Object\ObjectWithPhoneNumberInterface;
use Drupal\sms\PhoneNumberVerification\SmsPhoneNumberVerificationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Notifier\Recipient\SmsRecipientInterface;

/**
 * Removes SMS recipients if they don't match the required verification state.
 */
final readonly class FilterSmsRecipientsByVerification implements EventSubscriberInterface {

  /**
   * @internal
   */
  public function __construct(
    private ?SmsPhoneNumberVerificationInterface $verification,
  ) {
  }

  public function filterByVerification(Recipients $event): void {
    $options = $event->query->getOptions(QueryOptions::class) ?? QueryOptions::defaults();

    if ($this->verification === NULL || $options->verified === VerificationRequirement::Any) {
      // Don't filter recipients if verification service not set or required
      // verification state is any.
      return;
    }

    if (!$event->for instanceof ObjectWithPhoneNumberInterface && !$event->for instanceof EntityInterface) {
      return;
    }

    foreach ($event->getRecipients() as $recipient) {
      if (!$recipient instanceof SmsRecipientInterface) {
        continue;
      }

      $phoneNumber = $recipient->getPhone();
      if ($phoneNumber === '') {
        continue;
      }

      $isVerified = $this->verification->isVerified($event->for, PhoneNumber::create($phoneNumber));
      if ($options->verified === VerificationRequirement::Verified && $isVerified !== Verified::Verified) {
        $event->removeRecipient($recipient);
      }
      elseif ($options->verified === VerificationRequirement::Unverified && $isVerified !== Verified::Unverified) {
        $event->removeRecipient($recipient);
      }
    }
  }

  public static function getSubscribedEvents(): array {
    return [Recipients::class => ['filterByVerification', -1024]];
  }

}
