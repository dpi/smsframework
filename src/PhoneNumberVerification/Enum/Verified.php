<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumberVerification\Enum;

enum Verified {

  case Verified;
  case Unverified;

  public function databaseValue(): bool {
    return match ($this) {
      static::Verified => TRUE,
      static::Unverified => FALSE,
    };
  }

}
