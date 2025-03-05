<?php

declare(strict_types=1);

namespace Drupal\Tests\sms\Trait;

use Drupal\sms\Entity\PhoneNumberVerification;
use Drupal\sms\Entity\PhoneNumberVerificationInterface;

trait SmsFrameworkTestTrait {

  /**
   * Gets the last phone number verification that was created, or NULL.
   */
  protected static function getLastVerification(): ?PhoneNumberVerificationInterface {
    $verification_storage = \Drupal::entityTypeManager()
      ->getStorage(PhoneNumberVerification::ENTITY_TYPE_ID);

    $verification_ids = $verification_storage->getQuery()
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();
    /** @var array<int, \Drupal\sms\Entity\PhoneNumberVerification> $verifications */
    $verifications = $verification_storage->loadMultiple($verification_ids);

    return $verifications !== [] ? $verifications[\array_key_first($verifications)] : NULL;
  }

  /**
   * @throws \Exception
   */
  protected function getLastVerificationOrException(): PhoneNumberVerificationInterface {
    return static::getLastVerification() ?? throw new \LogicException('Expected a verification but none was found.');
  }

}
