<?php

/**
 * @file
 * Contains \Drupal\sms_user\ActiveHoursInterface.
 */

namespace Drupal\sms_user;

use Drupal\user\UserInterface;
use Drupal\sms\Entity\SmsMessageInterface;

/**
 * Defines interface for the user active hours service.
 */
interface ActiveHoursInterface {

  /**
   * Determine if the current time of a user is within permitted hour ranges.
   *
   * @param \Drupal\user\UserInterface $user
   *   A user entity.
   * @param string $now
   *   The local time for the user. Defaults to current time.
   *
   * @return bool
   *   Whether the current time for a user is within active hours.
   */
  public function inHours(UserInterface $user, $now = 'now');

  public function findNextTime(UserInterface $user, $now = 'now');

  public function delaySmsMessage(SmsMessageInterface &$sms_message);

}
