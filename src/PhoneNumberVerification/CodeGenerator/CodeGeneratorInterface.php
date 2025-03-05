<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumberVerification\CodeGenerator;

interface CodeGeneratorInterface {

  public function generateCode(VerificationCodeContext $context): VerificationCodeInterface;

}
