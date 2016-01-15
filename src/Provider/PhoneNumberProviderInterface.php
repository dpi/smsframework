<?php

/**
 * @file
 * Contains \Drupal\sms\Provider\PhoneNumberProviderInterface.
 */

namespace Drupal\sms\Provider;

use Drupal\Core\Entity\EntityInterface;
use Drupal\sms\Message\SmsMessageInterface;

interface PhoneNumberProviderInterface {

  /**
   * x
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   x
   *
   * @throws \Drupal\sms\Exception\PhoneNumberConfiguration
   *   Thrown if entity is not configured with a phone number.
   *
   * @return int[]
   *   x
   */
  public function getPhoneNumbers(EntityInterface $entity);

  /**
   * x
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   x
   * @param \Drupal\sms\Message\SmsMessageInterface $sms_message
   *   x
   *
   * @return mixed
   *   x
   */
  public function sendMessage(EntityInterface $entity, SmsMessageInterface $sms_message);

  public function getPhoneNumberSettings($entity_type_id, $bundle);

  /**
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @return mixed
   *
   * @throws PhoneNumberConfiguration
   */
  public function getPhoneNumberSettingsForEntity(EntityInterface $entity);

  /**
   * @return \Drupal\sms\Entity\PhoneNumberVerificationInterface|NULL
   */
  public function getPhoneVerificationCode($code);

  /**
   * @return \Drupal\sms\Entity\PhoneNumberVerificationInterface|NULL
   */
  public function getPhoneVerification(EntityInterface $entity, $phone_number);
  public function newPhoneVerification(EntityInterface $entity, $phone_number);

}
