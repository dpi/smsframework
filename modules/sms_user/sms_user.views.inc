<?php

/**
 * @file
 * The basic views fields available for sms_user.
 */

/**
 * Implements hook_views_data()
 */
function sms_user_views_data() {
  $data['sms_user']['table']['group'] = t('SMS User');
  $data['sms_user']['table']['join'] = array(
    'users' => array(
      'left_field' => 'uid',
      'field' => 'uid',
    ),
  );
  $data['sms_user']['number'] = array(
    'title' => t('Mobile Number'),
    'help' => t('Display the user\'s mobile number.'),
    'field' => array(
      'id' => 'sms_number',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'standard',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
  $data['sms_user']['status'] = array(
    'title' => t('Mobile Status'),
    'help' => t('Display the status of the user\'s mobile number confirmation.'),
    'field' => array(
      'id' => 'sms_status',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'sms_status',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
  return $data;
}
