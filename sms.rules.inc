<?php

/**
 * @file
 * Rules module support of the smsframework.
 */

/**
 * Implements hook_rules_event_info().
 */
function sms_rules_event_info() {
  return array(
    'sms_incoming' => array(
      'label' => t('When a SMS message is received.'),
      'module' => 'SMS Framework',
      'arguments' => array(
        'number' => array(
          'label' => t('Number'),
          'type' => 'sms_number',
        ),
        'message' => array(
          'label' => t('Message'),
          'type' => 'sms_message',
        ),
      ),
    ),
  );
}

/**
 * Implements hook_token_values().
 */
function sms_token_values($type, $object = NULL, $options = array()) {
  $values = array();
  if ($type == 'sms_message') {
    $values['message'] = $object;
    
    $words = explode(' ', $object);
    
    foreach ($words as $key => $word) {
      $values['word-' . $key] = $word;
    }
  }
  elseif ($type == 'sms_number') {
    $values['number'] = $object;
  }
  
  return $values;
}

/**
 * Implements hook_token_list().
 */
function sms_token_list($type = 'all') {
  $tokens = array();
  switch ($type) {
    case 'sms_message':
      $tokens['sms_message']['message'] = t('SMS message text');
      $tokens['sms_message']['word-??'] = t('Individual word of the message text as specified by the ??.');
      break;
    case 'sms_number':
      $tokens['sms_number']['number'] = t('The origin mobile number');
      break;
  }
  
  return $tokens;
}
