<?php

/**
 * @file
 * SMS User Rules Implementation.
 *
 */

use Drupal\sms\Message\SmsMessage;

/*
 * Implements hook_rules_event_info().
 * @ingroup rules
 */
function sms_user_rules_event_info() {
  return array(
    'sms_user_validated' => array(
      'label' => t('User validated SMS code'),
      'module' => 'SMS',
      'arguments' => array(
        'user' => array(
          'type' => 'user',
          'label' => t('User validated SMS code'),
        ),
      ),
    ),
    'sms_user_removed' => array(
      'label' => t('User removed mobile phone number'),
      'module' => 'SMS',
      'arguments' => array(
        'user' => array(
          'type' => 'user',
          'label' => t('User removed mobile phone number'),
        ),
      ),
    ),
  );
}

/**
 * Implements hook_rules_action_info().
 */
function sms_user_rules_action_info() {
  return array(
    'sms_action_user_send' => array(
      'label' => t('Send SMS message to user'),
      'arguments' => array(
        'user' => array(
          'type' => 'user',
          'label' => t('User'),
        ),
        'message' => array(
          'type' => 'string',
          'label' => t('Message'),
        ),
      ),
      'module' => 'SMS Framework',
    ),
    'sms_action_match_user' => array(
      'label' => t('Match mobile number to user'),
      'arguments' => array(
        'number' => array(
          'type' => 'string',
          'label' => t('Mobile number'),
        ),
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
 */
function sms_action_user_send($user, $message) {
  /** @var \Drupal\sms\Provider\PhoneNumberProviderInterface $phone_number_provider */
  $phone_number_provider = \Drupal::service('sms.phone_number');
  $sms_message = new SmsMessage();
  $sms_message->setMessage($message);
  $phone_number_provider->sendMessage($user, $sms_message);
}

/**
 * Rules action callback to match number to user.
 */
function sms_action_match_user($number) {
  $uid = sms_user_get_uid($number);
  return array('user_loaded' => user_load($user->uid));
}

