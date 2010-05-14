<?php
// $Id$
/**
 * SMS Framework core module: Admin settings form functions
 *
 * @package sms
 */ 

/**
 * Implementation of hook_theme().
 */
/*
function sms_theme() {
  return array(
    'sms_admin_default_form' => array(
      'arguments' => array('form' => NULL),  
    )
  );
}
*/

function sms_admin_default_form() {
  $gateways = sms_gateways();
  
  foreach ($gateways as $identifier => $gateway) {
    $active = ($identifier == variable_get('sms_default_gateway', 'log'));
    $options[$identifier] = '';
    $form[$gateway['name']]['id'] = array('#value' => $identifier);
    if (function_exists($gateway['configure form'])) {
      $form[$gateway['name']]['configure'] = array('#value' => l(t('configure'), 'admin/smsframework/gateways/'. $identifier));
    }
    else {
      $form[$gateway['name']]['configure'] = array('#value' => t('No configuration options'));
    }
  }
  $form['default'] = array('#type' => 'radios', '#options' => $options, '#default_value' => variable_get('sms_default_gateway', 'log'));
  
  $form['submit'] = array(
    '#type' => 'submit', 
    '#value' => t('Set default gateway'),
  );
  
  return $form;
}

function sms_admin_default_form_submit($form, &$form_state) {
  // Process form submission to set the default gateway
  if ($form_state['values']['default']) {
    drupal_set_message(t('Default gateway updated.'));
    variable_set('sms_default_gateway', $form_state['values']['default']);
  }
}

function theme_sms_admin_default_form($form) {
  $rows = array();
  foreach ($form as $name => $element) {
    if (isset($element['id']) && is_array($element['id'])) {
      $rows[] = array(
        drupal_render($form['default'][$element['id']['#value']]),
        check_plain($name),
        drupal_render($element['configure']),
      );
      unset($form[$name]);
    }
  }
  $header = array(t('Default'), t('Name'), array('data' => t('Operations'), 'colspan' => 1));
  $output .= theme('table', $header, $rows);
  $output .= drupal_render($form);

  return $output;
}

function sms_admin_gateway_form(&$form_state, $gateway_id) {
  $gateway = sms_gateways('gateway', $gateway_id);
  if ($gateway && function_exists($gateway['configure form'])) {
    drupal_set_title(t('@gateway configuration', array('@gateway' => $gateway['name'])));
    $form = $gateway['configure form']($gateway['configuration']);

    $form['submit'] = array(
      '#type' => 'submit', 
      '#value' => t('Save'),
    );
    $form['gateway'] = array('#type' => 'value', '#value' => $gateway);

    return $form;
  }
  drupal_not_found();
  exit();
}

/**
 * Validation for gateway configuration. If the gateway defines a validation
 * function it uses that.
 */
function sms_admin_gateway_form_validate($form, &$form_state) {
  // Pass validation to gateway
  $function = $form_state['values']['gateway']['configure form'] .'_validate';
  if (function_exists($function)) {
    $function($form, $form_state);
  }
}

function sms_admin_gateway_form_submit($form, &$form_state) {
  $gateway = $form_state['values']['gateway'];
  
  // Remove uneccesary values
  unset($form_state['values']['op'], $form_state['values']['submit'], $form_state['values']['gateway'], $form_state['values']['form_token'], $form_state['values']['form_id']);
  variable_set('sms_'. $gateway['identifier'] .'_settings', $form_state['values']);
  drupal_set_message(t('The gateway settings have been saved.'));
  $form_state['redirect'] = 'admin/smsframework/gateways';
}


