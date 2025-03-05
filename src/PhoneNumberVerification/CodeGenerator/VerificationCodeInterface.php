<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumberVerification\CodeGenerator;

interface VerificationCodeInterface {

  /**
   * @phpstan-return non-empty-string
   */
  public function getCode(): string;

}
