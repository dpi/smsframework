<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumber;

/**
 * @internal
 *
 * @phpstan-import-type SmsPhoneNumberMapping from \Drupal\sms\SmsCompilerPass
 */
final class BundleConfiguration {

  public function __construct(
    public readonly string $entityTypeId,
    public readonly string $bundle,
    public readonly string $fieldName,
    public readonly bool $verifications,
  ) {
  }

  /**
   * @phpstan-param SmsPhoneNumberMapping $map
   */
  public static function fromRaw(array $map): static {
    return new static(
      $map['entity_type'],
      $map['bundle'],
      $map['field_name'],
      $map['verifications'] ?? TRUE,
    );
  }

}
