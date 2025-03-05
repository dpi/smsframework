<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumberVerification\CodeGenerator;

final class VerificationCode implements VerificationCodeInterface {

  /**
   * @phpstan-param non-empty-string $code
   */
  public function __construct(
    private string $code,
  ) {
  }

  public function getCode(): string {
    return $this->code;
  }

}
