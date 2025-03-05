<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumber;

use Drupal\sms\PhoneNumberVerification\Enum\VerificationRequirement;

final class QueryOptions {

  private const USE_NAMED_PARAMETERS = 'Constructing this object without named parameters is not supported.';

  public function __construct(
    ?string $useNamedParameters = self::USE_NAMED_PARAMETERS,
    public readonly VerificationRequirement $verified = VerificationRequirement::Verified,
  ) {
    if (self::USE_NAMED_PARAMETERS !== $useNamedParameters) {
      throw new \LogicException(self::USE_NAMED_PARAMETERS);
    }
  }

  /**
   * @internal
   */
  public static function defaults(): static {
    return new static();
  }

}
