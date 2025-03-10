<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumber\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\sms\PhoneNumber\PhoneNumber;
use Drupal\sms\PhoneNumber\PhoneNumbers;
use Drupal\sms\PhoneNumber\QueryOptions;
use Drupal\sms\PhoneNumberVerification\Object\ObjectWithPhoneNumberInterface;

final class ObjectPhoneNumbers {

  private PhoneNumbers $phoneNumbers;

  public function __construct(
    public readonly ObjectWithPhoneNumberInterface|EntityInterface $for,
    public readonly QueryOptions $options,
  ) {
    $this->phoneNumbers = new PhoneNumbers();
  }

  /**
   * @return $this
   */
  public function addPhoneNumber(PhoneNumber $phone_number) {
    $this->phoneNumbers[] = $phone_number;

    return $this;
  }

  /**
   * @phpstan-return iterable<\Drupal\sms\PhoneNumber\PhoneNumberInterface>
   */
  public function getPhoneNumbers(): iterable {
    return $this->phoneNumbers;
  }

}
