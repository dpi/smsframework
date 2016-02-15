<?php


namespace Drupal\sms\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

interface PhoneNumberVerificationInterface extends ContentEntityInterface {

  /**
   * Gets the phone number verification creation timestamp.
   *
   * @return int
   *   Creation timestamp of the phone number verification.
   */
  public function getCreatedTime();

  /**
   * Get the entity for the phone number verification.
   *
   * @return \Drupal\Core\Entity\EntityInterface|NULL
   *   The entity for the phone number verification, or NULL if it is missing.
   */
  public function getEntity();

  /**
   * Set the entity for the phone number verification.
   *
   * @param $entity
   *   The entity for the phone number verification.
   *
   * @return $this
   *   Return phone number verification for chaining.
   */
  public function setEntity($entity);

  /**
   * Get the phone number for the phone number verification.
   *
   * @return string
   *   The phone number for the phone number verification.
   */
  public function getPhoneNumber();

  /**
   * Set the phone number for the phone number verification.
   *
   * @param string $phone_number
   *   The phone number for the phone number verification.
   *
   * @return $this
   *   Return phone number verification for chaining.
   */
  public function setPhoneNumber($phone_number);

  /**
   * Get the verification code for the phone number verification.
   *
   * @return string
   *   The verification code for the phone number verification.
   */
  public function getCode();

  /**
   * Set the verification code for the phone number verification.
   *
   * @param string
   *   Set the verification code for the phone number verification.
   *
   * @return $this
   *   Return phone number verification for chaining.
   */
  public function setCode($code);

  /**
   * Get the status for the phone number verification.
   *
   * Default value is FALSE (not verified).
   *
   * @return bool
   *   Whether the phone number is verified.
   */
  public function getStatus();

  /**
   * Set the status for the phone number verification.
   *
   * @param bool
   *   Whether the phone number is verified.
   *
   * @return $this
   *   Return phone number verification for chaining.
   */
  public function setStatus($status);

}