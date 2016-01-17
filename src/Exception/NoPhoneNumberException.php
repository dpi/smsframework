<?php

/**
 * @file
 * Contains \Drupal\sms\Exception\NoPhoneNumberException.
 */

namespace Drupal\sms\Exception;

/**
 * Thrown if code incorrectly assumes an entity has a phone number.
 *
 * Call \Drupal::service('sms.phone_number')->getPhoneNumbers() if unsure.
 */
class NoPhoneNumberException extends SmsException {}
