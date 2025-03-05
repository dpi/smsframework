<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumberVerification\Enum;

enum VerificationRequirement {

  case Verified;
  case Unverified;
  case Any;

}
