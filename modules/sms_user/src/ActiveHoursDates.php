<?php

/**
 * @file
 * Contains \Drupal\sms_user\ActiveHoursDates.
 */

namespace Drupal\sms_user;

use Drupal\Core\Datetime\DrupalDateTime;

class ActiveHoursDates {

  function __construct(DrupalDateTime $start, DrupalDateTime $end) {
    $this->start = $start;
    $this->end = $end;
  }

  function getStartDate() {
    return $this->start;
  }

  function getEndDate() {
    return $this->end;
  }

}
