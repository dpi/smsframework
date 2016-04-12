<?php

/**
 * @file
 * Contains \Drupal\sms_user\ActiveHours.
 */

namespace Drupal\sms_user;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\sms\Entity\SmsMessageInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Defines the user active hours service.
 */
class ActiveHours implements ActiveHoursInterface {

  /**
   * The config factory used by the config entity query.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface;
   */
  protected $configFactory;

  /**
   * Constructs a QueryFactory object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config storage used by the config entity query.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * @inheritdoc
   */
  public function inHours(UserInterface $user, $now = 'now') {
    // Users current time
    $timezone = $user->getTimeZone();
    $date = new DrupalDateTime($now, $timezone);

    $settings = $this->configFactory
      ->get('sms_user.settings')
      ->get('active_hours');

    // We're in hours if active hours feature is disabled.
    if (!empty($settings['status'])) {
      return TRUE;
    }

    foreach ($settings['ranges'] as $range) {
      $start = new DrupalDateTime($range['start'], $timezone);
      $end = new DrupalDateTime($range['end'], $timezone);
      if ($date >= $start && $date <= $end) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * @inheritdoc
   */
  public function findNextTime(UserInterface $user, $now = 'now') {
    $timezone = $user->getTimeZone();
    $now = new DrupalDateTime($now, $timezone);

    // Find the next time.
    $settings = $this->configFactory
      ->get('sms_user.settings')
      ->get('active_hours');

    $dates = [];
    foreach ($settings['ranges'] as $range) {
      $date['start'] = new DrupalDateTime($range['start'], $timezone);
      $date['end'] = new DrupalDateTime($range['end'], $timezone);
      // The end date may have already passed.
      if ($now < $date['end']) {
        $dates[] = $date;
      }
    }

    // Sort so nearest date is closest.
    usort($dates, function($a, $b) {
      if ($a['start'] == $b['end']) {
        return 0;
      }
      return $a['start'] < $b['start'] ? -1 : 1;
    });

    return reset($dates);
  }

  /**
   * @inheritdoc
   */
  public function delaySmsMessage(SmsMessageInterface &$sms_message) {
    $recipient = $sms_message->getRecipientEntity();
    if ($sms_message->isAutomated() && $recipient instanceof UserInterface) {
      if (!$this->inHours($recipient) && ($range = $this->findNextTime($recipient))) {
        $sms_message->setSendTime($range['start']->format('U'));
      }
    }
  }

}
