<?php
/**
 * @file
 * SMS Framework Message Tracking feature module: views
 *
 * @package sms
 * @subpackage sms_track
 */


/**
 * Implement hook_handler_field().
 */
class sms_track_handler_field_direction extends views_handler_field {
  function render($values) {
    $value = $values->{$this->field_alias};
    switch ($value) {
      case 0:
        return t('Out');
      case 1:
        return t('In');
    }
    // else
    return $value;
  }
}
