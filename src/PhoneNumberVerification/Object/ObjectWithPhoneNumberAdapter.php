<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumberVerification\Object;

use Drupal\Core\Entity\EntityInterface;

/**
 * @internal
 */
final class ObjectWithPhoneNumberAdapter implements ObjectWithPhoneNumberInterface {

  private function __construct(
    private readonly string $verifiedType,
    private readonly string $verifiedIdentifier,
  ) {
  }

  /**
   * @internal
   */
  public static function from(ObjectWithPhoneNumberInterface|EntityInterface $from): static {
    return new static(
      $from instanceof ObjectWithPhoneNumberInterface ? $from->getVerifiedType() : $from->getEntityTypeId(),
      (string) ($from instanceof ObjectWithPhoneNumberInterface ? $from->getVerifiedIdentifier() : $from->id()),
    );
  }

  /**
   * @internal
   */
  public static function fromRaw(string $verifiedType, string $verifiedIdentifier,): static {
    return new static($verifiedType, $verifiedIdentifier,);
  }

  public function getVerifiedType(): string {
    return $this->verifiedType;
  }

  public function getVerifiedIdentifier(): string {
    return $this->verifiedIdentifier;
  }

}
