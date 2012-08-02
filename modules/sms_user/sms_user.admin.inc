<?php

/**
 * @file
 * sms_user.admin.inc
 * SMS admin pages and functions.
 */

/**
 * SMS admin settings.
 */
function sms_user_admin_settings($form, &$form_state) {
  $form['sms_user_registration_form'] = array(
    '#type' => 'radios',
    '#title' => t('Show mobile fields during user registration'),
    '#description' => t('Specify if the site should collect mobile information during registration.'),
    '#options' => array(t('Disabled'), t('Optional'), t('Required')),
    '#default_value' => variable_get('sms_user_registration_form', 0),
  );

  $form['sms_user_confirmation_message'] = array(
    '#type' => 'textfield',
    '#title' => t('Confirmation message format'),
    '#default_value' => variable_get('sms_user_confirmation_message', '[site:name] confirmation code: '),
    '#description' => t('Specify the format for confirmation messages. Keep this as short as possible.'),
    '#size' => 140,
    '#maxlength' => 255,
  );

  // Add the token help to a collapsed fieldset at the end of the configuration page.
  $form['tokens']['token_help'] = array(
    '#type' => 'fieldset',
    '#title' => t('Available Tokens List'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['tokens']['token_help']['content'] = array(
    '#theme' => 'token_tree',
    '#token_types' => array(),
  );
  /*
  $form['tokens'] = array(
    '#type' => 'fieldset',
    '#title' => t('Available replacement patterns'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  $form['tokens']['content']['#value'] = theme('token_tree', array('token_types' => array('sms_user')));
  */
  $form['sms_user_sleep'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable sleep hours'),
    '#description' => t('If checked, users will be able to specifiy hours during which they will not receive messages from the site.'),
    '#default_value' => variable_get('sms_user_sleep', 1),
  );

  // Registration settings.
  $form['registration'] = array(
    '#type' => 'fieldset',
    '#title' => t('Registration settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['registration']['sms_user_registration_enabled'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable registration'),
    '#default_value' => variable_get('sms_user_registration_enabled', 0),
    '#description' => t('If selected, users can create user accounts via SMS.'),
  );
  $form['registration']['sms_user_allow_password'] = array(
    '#type' => 'checkbox',
    '#title' => t('Allow password creation'),
    '#default_value' => variable_get('sms_user_allow_password', 0),
    '#description' => t('If selected, the user will be allowed to include a password in their registration request -- the password will be the first word in the first line of the request.'),
  );
  $form['registration']['sms_user_new_account_message'] = array(
    '#type' => 'textarea',
    '#title' => t('New user message'),
    '#default_value' => variable_get('sms_user_new_account_message', ''),
    '#description' => t('The message that will be sent to newly registered users.  Leave empty for no message.'),
  );
  
  // Add the token help to a collapsed fieldset at the end of the registration page.
  $form['registration']['tokens']['token_help'] = array(
    '#type' => 'fieldset',
    '#title' => t('Available Tokens List'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['registration']['tokens']['token_help']['content'] = array(
    '#theme' => 'token_tree',
    '#token_types' => array(),
  );
  /*
  $form['registration']['tokens'] = array(
    '#type' => 'fieldset',
    '#title' => t('Available replacement patterns'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  $form['registration']['tokens']['content']['#value'] = theme('token_tree', array('token_types' => array('sms_user')));
  */
  $form['sms_user_max_chars'] = array(
    '#type' => 'textfield',
    '#default_value' => variable_get('sms_user_max_chars', SMS_USER_MAX_CHARS),
    '#size' => 3,
    '#title' => t('Maximum number of chars for SMS sending using actions'),
  );
  return system_settings_form($form);
}
