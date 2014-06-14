<?php

/**
 * @file
 * Contains GatewayConfigForm class
 */

namespace Drupal\sms\Form;

use \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Provides a configuration form for sms gateways.
 *
 * @TODO Implementing Gateways as Entities or Plugins would make this config
 * form more streamlined
 */
class GatewayConfigForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'sms_admin_gateway_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $gateway_id = NULL) {
    $gateway = sms_gateways('gateway', $gateway_id);
    if ($gateway && !empty($gateway['configure form']) && function_exists($gateway['configure form'])) {
      $form = $gateway['configure form']($gateway['configuration']);
      $form['#title'] = $this->t('@gateway configuration', array('@gateway' => $gateway['name']));

      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      );
      $form['gateway'] = array(
        '#type' => 'value',
        '#value' => $gateway,
      );
  
      return $form;
    }
    else {
      throw new NotFoundHttpException();
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    // Pass validation to gateway
    $function = $form_state['values']['gateway']['configure form'] . '_validate';
    if (function_exists($function)) {
      $function($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $gateway = $form_state['values']['gateway'];
    // Remove unnecessary values
    unset($form_state['values']['op'], $form_state['values']['submit'], $form_state['values']['gateway'], $form_state['values']['form_token'], $form_state['values']['form_id']);
    $this->config('sms.settings')
      ->set('gateway_settings.' . $gateway['identifier'], $form_state['values'])
      ->save();
    drupal_set_message($this->t('The gateway settings have been saved.'));
    $form_state['redirect'] = 'admin/config/smsframework/gateways';
  }

  /**
   * Title callback fo the menu
   */
  public function getTitle($gateway_id) {
    $gateway = sms_gateways('gateway', $gateway_id);
    return sprintf('%s gateway', $gateway['name']);
  }
}
