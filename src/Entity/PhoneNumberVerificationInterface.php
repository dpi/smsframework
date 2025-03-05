<?php

declare(strict_types=1);

namespace Drupal\sms\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\sms\PhoneNumber\PhoneNumberInterface;
use Drupal\sms\PhoneNumberVerification\CodeGenerator\VerificationCodeInterface;
use Drupal\sms\PhoneNumberVerification\Enum\Verified;
use Drupal\sms\PhoneNumberVerification\Object\ObjectWithPhoneNumberInterface;

/**
 * Interface for phone number verification entity.
 *
 * @internal
 *
 * This class is part of the PhoneNumberVerification component.
 */
interface PhoneNumberVerificationInterface extends ContentEntityInterface {

  /**
   * Gets the phone number verification creation timestamp.
   */
  public function getCreatedDate(): \DateTimeImmutable;

  /**
   * @return $this
   */
  public function setCreatedDate(\DateTimeInterface $created);

  /**
   * Gets the object for the phone number verification.
   *
   * @return \Drupal\sms\PhoneNumberVerification\Object\ObjectWithPhoneNumberInterface|\Drupal\Core\Entity\EntityInterface|null
   *   The entity or object for the phone number verification, or NULL if it is
   *   missing.
   */
  public function getFor(): ObjectWithPhoneNumberInterface|EntityInterface|null;

  /**
   * Sets the entity for the phone number verification.
   *
   * @return $this
   */
  public function setFor(ObjectWithPhoneNumberInterface|EntityInterface $for);

  public function getPhoneNumber(): PhoneNumberInterface;

  /**
   * @return $this
   */
  public function setPhoneNumber(PhoneNumberInterface $phoneNumber);

  public function getVerificationCode(): ?VerificationCodeInterface;

  /**
   * @return $this
   */
  public function setVerificationCode(VerificationCodeInterface $verificationCode);

  /**
   * @return $this
   */
  public function clearVerificationCode();

  public function getStatus(): Verified;

  /**
   * @return $this
   * @throws \InvalidArgumentException
   */
  public function setStatus(Verified $status);

}
