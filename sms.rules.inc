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
 * Implements hook_rules_action_info().
 *
 * @todo - this should be in the sms_user module.
 */
function sms_rules_action_info() {
  return array(
    'sms_action_user_send' => array(
      'label' => t('Send SMS message to user'),
      'arguments' => array(
        'user' => array('type' => 'user', 'label' => t('User')),
        'message' => array('type' => 'string', 'label' => t('Message')),
      ),
      'module' => 'SMS Framework',
    ),
    'sms_action_match_user' => array(
      'label' => t('Match mobile number to user'),
      'arguments' => array(
        'number' => array('type' => 'string', 'label' => t('Mobile number')),
      ),
      'new variables' => array(
        'user_loaded' => array(
          'type' => 'user',
          'label' => t('Loaded user'),
          // 'label callback' => 'rules_action_load_node_variable_label',
        ),
      ),
      'module' => 'SMS Framework',
    ),
  );
}

/**
 * Rules action callback to send sms message to user.
 *
 * @todo - this should be in the sms_user module.
 */
function sms_action_user_send($user, $message) {
  sms_user_send($user->uid, $message);
}

/**
 * Rules action callback to match number to user.
 *
 * @todo - this should be in the sms_user module.
 */
function sms_action_match_user($number) {
  $uid = sms_user_get_uid($number);
  return array('user_loaded' => user_load(array('uid' => $uid)));
}

/**
 * Implements hook_token_values().
 */
function sms_token_values($type, $object = NULL, $options = array()) {
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
