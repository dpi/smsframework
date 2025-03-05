<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumberVerification\CodeGenerator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\sms\PhoneNumber\PhoneNumberInterface;
use Drupal\sms\PhoneNumberVerification\Object\ObjectWithPhoneNumberInterface;

final class VerificationCodeContext {

  public function __construct(
    public ObjectWithPhoneNumberInterface|EntityInterface $for,
    public PhoneNumberInterface $phoneNumber,
  ) {
  }

}
