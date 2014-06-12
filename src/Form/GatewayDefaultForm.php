<?php

/**
 * @file
 * Contains GatewayDefaultForm class
 */

namespace Drupal\sms\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Provides a configuration form for setting the default gateway.
 */
class GatewayDefaultForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'sms_admin_default_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $gateways = sms_gateways();
    $default = sms_default_gateway_id();
    $options = array();
  
    foreach ($gateways as $identifier => $gateway) {
      $active = ($identifier == $default);
      $options[$identifier] = '';
      $form[$gateway['name']]['id'] = array('#markup' => $identifier);
      if ( isset($gateway['configure form'] ) and ( function_exists($gateway['configure form'] ))) {
        $form[$gateway['name']]['configure'] = array('#markup' => l(t('configure'), 'admin/smsframework/gateways/' . $identifier));
      }
      else {
        $form[$gateway['name']]['configure'] = array('#markup' => t('No configuration options'));
      }
    }
  
    $form['default'] = array(
      '#type' => 'radios',
      '#options' => $options,
      '#default_value' => $default,
    );
  
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Set default gateway'),
    );
  
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // Process form submission to set the default gateway
    if ($form_state['values']['default']) {
      drupal_set_message(t('Default gateway updated.'));
      
      $config = $this->configFactory->get('sms.settings');
      $config->set('default_gateway', $form_state['values']['default'])
        ->save();
    }
  }
}
