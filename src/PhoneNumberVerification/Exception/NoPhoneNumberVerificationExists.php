<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumberVerification\Exception;

use Drupal\Core\Entity\EntityInterface;
use Drupal\sms\LogReference;
use Drupal\sms\PhoneNumber\PhoneNumberInterface;
use Drupal\sms\PhoneNumberVerification\Object\ObjectWithPhoneNumberAdapter;
use Drupal\sms\PhoneNumberVerification\Object\ObjectWithPhoneNumberInterface;

final class NoPhoneNumberVerificationExists extends \Exception {

  public function __construct(
    ObjectWithPhoneNumberInterface|EntityInterface $for,
    PhoneNumberInterface $phoneNumber,
  ) {
    parent::__construct(
      message: \sprintf(\sprintf('No phone number verification code for `%s` and phone number `%s`', LogReference::create(ObjectWithPhoneNumberAdapter::from($for)), $phoneNumber->getPhoneNumber())),
    );
  }

}
