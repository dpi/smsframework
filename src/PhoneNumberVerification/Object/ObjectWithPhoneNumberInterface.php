<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumberVerification\Object;

interface ObjectWithPhoneNumberInterface {

  public function getVerifiedType(): string;

  public function getVerifiedIdentifier(): string;

}
