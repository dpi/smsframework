<?php

/**
 * @file
 * Contains \Drupal\sms_user\AccountRegistrationInterface.
 */

namespace Drupal\sms_user;

use Drupal\sms\Entity\SmsMessageInterface;

/**
 * Defines interface for the account registration service.
 */
interface AccountRegistrationInterface {

  /**
   * Process an incoming SMS to see if a new account should be created.
   *
   * @param \Drupal\sms\Entity\SmsMessageInterface $sms_message
   *   An incoming SMS message.
   *   @todo change this to standard sms message entity once the sender method
   *   is repurposed / or new method added, for sender number.
   */
  public function createAccount(SmsMessageInterface $sms_message);

}
