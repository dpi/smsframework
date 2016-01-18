<?php

/**
 * @file
 * Contains \Drupal\sms\Provider\PhoneNumberProviderInterface.
 */

namespace Drupal\sms\Provider;

use Drupal\Core\Entity\EntityInterface;
use Drupal\sms\Message\SmsMessageInterface;

/**
 * Interface for phone number provider.
 */
interface PhoneNumberProviderInterface {

  /**
   * Get phone numbers for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity to get phone numbers.
   * @param bool $verified
   *   Whether the returned phone numbers must be verified.
   *
   * @return string[int]
   *   An array of phone numbers, keyed by original field item index.
   *
   * @throws \Drupal\sms\Exception\PhoneNumberSettingsException
   *   Thrown if entity is not configured for phone numbers.
   */
  public function getPhoneNumbers(EntityInterface $entity, $verified);

  /**
   * Send an SMS to an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to send an SMS.
   * @param \Drupal\sms\Message\SmsMessageInterface $sms_message
   *   The SMS message to send to the entity.
   *
   * @return \Drupal\sms\Message\SmsMessageResultInterface|FALSE
   *   The message result from the gateway.
   *
   * @throws \Drupal\sms\Exception\NoPhoneNumberException
   *   Thrown if entity does not have a phone number.
   */
  public function sendMessage(EntityInterface $entity, SmsMessageInterface $sms_message);

  /**
   * Get phone number settings for a bundle.
   *
   * @param $entity_type_id
   *   The entity type ID of the bundle.
   * @param $bundle
   *   An entity bundle.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   A 'sms.phone.*' configuration object.
   */
  public function getPhoneNumberSettings($entity_type_id, $bundle);

  /**
   * Get phone number settings for the bundle of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get settings.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   A 'sms.phone.*' configuration object.
   *
   * @throws \Drupal\sms\Exception\PhoneNumberSettingsException
   *   Thrown if entity is not configured for phone numbers.
   */
  public function getPhoneNumberSettingsForEntity(EntityInterface $entity);

  /**
   * Checks if there is a phone number verification for a code.
   *
   * @param string $code
   *   A string to check is a verification code.
   *
   * @return \Drupal\sms\Entity\PhoneNumberVerificationInterface|NULL
   *   A phone number verification entity, or NULL if $code is not a valid
   *   verification code.
   */
  public function getPhoneVerificationByCode($code);

  /**
   * Get a phone number verification for an entity and phone number pair.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity to get phone number verification.
   * @param string $phone_number
   *   A phone number.
   *
   * @return \Drupal\sms\Entity\PhoneNumberVerificationInterface|NULL
   */
  public function getPhoneVerificationByEntity(EntityInterface $entity, $phone_number);

  /**
   * Generates a phone number verification for an entity and phone number pair.
   *
   * The phone number verification is immediately saved to storage, and an
   * SMS is sent to the phone number for verification.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity to get phone number verification.
   * @param string $phone_number
   *   A phone number.
   *
   * @return \Drupal\sms\Entity\PhoneNumberVerificationInterface|NULL
   *   A phone number verification.
   */
  public function newPhoneVerification(EntityInterface $entity, $phone_number);

}
