<?php

// $Id$

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
      'label' => t('When a SMS message is received'),
      'access callback' => TRUE, # XXX
      'module' => 'sms',
      'group' => t('SMS Framework'),
      'variables' => array(
        'sms' => array(
          'label' => t('SMS Information'),
          'description' => t('The SMS information XXXblah blah message.'),
          'type' => 'sms',
        ),
      ),
    ),
  );
}

