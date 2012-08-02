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
      'label' => t('A SMS message is received'),
      'access callback' => 'sms_rules_access_callback',
      'module' => 'sms',
      'group' => t('SMS Framework'),
      'variables' => array(
        'sms' => array(
          'label' => t('SMS Message'),
          'description' => t('The SMS message and metadata.'),
          'type' => 'sms',
        ),
      ),
    ),
  );
}

/**
 * Implements hook_rules_data_info().
 */
function sms_rules_data_info() {
  return array(
    'sms' => array(
      'label' => t('SMS Message'),
      'wrap' => TRUE,
      'property info' => _sms_rules_sms_info(),
    ),
  );
}

/**
 * Defines property info for sms messages.
 */
function _sms_rules_sms_info() {
  return array(
    'number' => array(
      'type' => 'text',
      'label' => t('The number which sent the SMS message'),
    ),
    'message' => array(
      'type' => 'text',
      'label' => ('The SMS message'),
    ),
  );
}

/**
 * Implements hook_rules_action_info().
 */
function sms_rules_action_info() {
  return array(
    'sms_send' => array(
      'label' => t('Send sms'),
      'group' => t('SMS'),
      'parameter' => array(
        'to' => array(
          'type' => 'text',
          'label' => t('To'),
          'description' => t('The destination SMS number'),
        ),
        'message' => array(
          'type' => 'text',
          'label' => t('Message'),
          'description' => t("The sms message body."),
        ),
      ),
      'base' => 'sms_rules_action_sms_send',
      'access callback' => 'sms_rules_access_callback',
    ),
  );
}

/**
 * Action Implementation: Send sms.
 */
function sms_rules_action_sms_send($to, $message) {
  sms_send($to, $message);
}

/**
 * Access callback for the SMS Rules integration.
 */
function sms_rules_access_callback($type) {
  return user_access('administer smsframework');
}
