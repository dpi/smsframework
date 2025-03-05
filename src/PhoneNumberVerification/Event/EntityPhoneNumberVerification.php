<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumberVerification\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\sms\PhoneNumber\PhoneNumberInterface;
use Drupal\sms\PhoneNumberVerification\Object\ObjectWithPhoneNumberInterface;

/**
 * Allows custom code to prevent automatic creation of verifications.
 */
final class EntityPhoneNumberVerification {

  private function __construct(
    private ObjectWithPhoneNumberInterface|EntityInterface $for,
    private PhoneNumberInterface $phoneNumber,
    private bool $shallSendVerification = TRUE,
  ) {
  }

  /**
   * @internal
   */
  public static function create(
    ObjectWithPhoneNumberInterface|EntityInterface $for,
    PhoneNumberInterface $phoneNumber,
  ): static {
    return new static($for, $phoneNumber);
  }

  /**
   * Whether SMS Framework should create verifications and dispatch SMS.
   *
   * Normally this would be turned off for specific entities, or if you are
   * managing the creation of verifications (via methods on
   * SmsPhoneNumberVerification) yourself.
   *
   * @return $this
   */
  public function shallSendVerification(bool $shallSendVerification) {
    $this->shallSendVerification = $shallSendVerification;

    return $this;
  }

  public function isSendingVerification(): bool {
    return $this->shallSendVerification;
  }

  public function getObject(): ObjectWithPhoneNumberInterface|EntityInterface {
    return $this->for;
  }

  public function getPhoneNumber(): PhoneNumberInterface {
    return $this->phoneNumber;
  }

}
