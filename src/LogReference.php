<?php

declare(strict_types=1);

namespace Drupal\sms;

use Drupal\Core\Entity\EntityInterface;
use Drupal\sms\PhoneNumberVerification\Object\ObjectWithPhoneNumberAdapter;
use Drupal\sms\PhoneNumberVerification\Object\ObjectWithPhoneNumberInterface;

/**
 * @internal
 */
final class LogReference implements \Stringable {

  private function __construct(
    private string $label,
  ) {
  }

  public static function create(ObjectWithPhoneNumberInterface|EntityInterface|null $for): static {
    $for = $for !== NULL ? ObjectWithPhoneNumberAdapter::from($for) : NULL;
    return new static(
      label: ($for !== NULL
        ? \sprintf('%s:%s', $for->getVerifiedType(), $for->getVerifiedIdentifier())
        : 'Missing entity'),
    );
  }

  public function __toString(): string {
    return \sprintf('👤 %s', $this->label);
  }

}
