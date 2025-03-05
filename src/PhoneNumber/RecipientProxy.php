<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumber;

use Symfony\Component\Notifier\Recipient\SmsRecipientInterface;

/**
 * @internal
 */
final class RecipientProxy implements SmsRecipientInterface {

  private function __construct(
    private readonly PhoneNumberInterface $phoneNumber,
  ) {}

  /**
   * @internal
   */
  public static function createFromPhoneNumber(PhoneNumberInterface $phoneNumber): static {
    return new static($phoneNumber);
  }

  public function getPhone(): string {
    return $this->phoneNumber->getPhoneNumber();
  }

}
