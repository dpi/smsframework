<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumberVerification;

use Drupal\Core\Entity\EntityInterface;
use Drupal\sms\Entity\PhoneNumberVerificationInterface;
use Drupal\sms\PhoneNumber\PhoneNumberInterface;
use Drupal\sms\PhoneNumberVerification\CodeGenerator\VerificationCodeInterface;
use Drupal\sms\PhoneNumberVerification\Enum\Verified;
use Drupal\sms\PhoneNumberVerification\Object\ObjectWithPhoneNumberInterface;

interface SmsPhoneNumberVerificationInterface {

  public function isVerified(ObjectWithPhoneNumberInterface|EntityInterface $for, PhoneNumberInterface $phoneNumber): Verified;

  public function getPhoneNumberVerification(ObjectWithPhoneNumberInterface|EntityInterface $for, PhoneNumberInterface $phoneNumber): ?PhoneNumberVerificationInterface;

  public function newPhoneVerification(ObjectWithPhoneNumberInterface|EntityInterface $for, PhoneNumberInterface $phoneNumber): void;

  public function claimableVerificationByCodeExists(VerificationCodeInterface $verificationCode): bool;

  /**
   * @throws \Drupal\sms\PhoneNumberVerification\Exception\NoPhoneNumberVerificationExists
   */
  public function verify(ObjectWithPhoneNumberInterface|EntityInterface $for, PhoneNumberInterface $phoneNumber): void;

  /**
   * @throws \Drupal\sms\PhoneNumberVerification\Exception\NoPhoneNumberVerificationByCodeExists
   */
  public function verifyByCode(VerificationCodeInterface $verificationCode): void;

  public function purgeExpiredVerifications(): void;

  /**
   * Deletes all verifications for an entity.
   */
  public function deletePhoneVerifications(EntityInterface $entity): void;

}
