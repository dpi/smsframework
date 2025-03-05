<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumber;

interface PhoneNumberInterface extends \Stringable {

  /**
   * @phpstan-return non-empty-string
   */
  public function getPhoneNumber(): string;

}
