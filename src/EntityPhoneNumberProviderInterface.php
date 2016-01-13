<?php

/**
 * @file
 * Contains \Drupal\sms\EntityPhoneNumberProviderInterface.
 */

namespace Drupal\sms;

use Drupal\Core\Entity\EntityInterface;
use Drupal\sms\Message\SmsMessageInterface;

interface EntityPhoneNumberProviderInterface {

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
  function getPhoneNumbers(EntityInterface $entity);

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
  function sendMessage(EntityInterface $entity, SmsMessageInterface $sms_message);

}
