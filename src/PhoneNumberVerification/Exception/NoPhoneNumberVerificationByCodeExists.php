<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumberVerification\Exception;

use Drupal\sms\PhoneNumberVerification\CodeGenerator\VerificationCodeInterface;

final class NoPhoneNumberVerificationByCodeExists extends \Exception {

  public function __construct(
    VerificationCodeInterface $verificationCode,
  ) {
    parent::__construct(
      message: \sprintf('No phone number verification code exists with code: %s', $verificationCode->getCode()),
    );
  }

}
