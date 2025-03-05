<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumberVerification\CodeGenerator;

final class NumberCodeGenerator implements CodeGeneratorInterface {

  public function generateCode(VerificationCodeContext $context): VerificationCodeInterface {
    // Cryptographically random number.
    return new VerificationCode((string) \random_int(100000, 999999));
  }

}
