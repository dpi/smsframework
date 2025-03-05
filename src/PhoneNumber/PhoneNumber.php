<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumber;

final class PhoneNumber implements PhoneNumberInterface {

  /**
   * @phpstan-param non-empty-string $phoneNumber
   */
  private function __construct(
    private string $phoneNumber,
  ) {
  }

  /**
   * @phpstan-param non-empty-string $phoneNumber
   */
  public static function create(string $phoneNumber): static {
    return new static($phoneNumber);
  }

  /**
   * @phpstan-return non-empty-string
   */
  public function getPhoneNumber(): string {
    return $this->phoneNumber;
  }

  public function __toString(): string {
    return \sprintf('📞 %s', $this->phoneNumber);
  }

}
