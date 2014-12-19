<?php

/**
 * @file
 * Contains GatewayConfigForm class
 */

namespace Drupal\sms\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
  public function buildForm(array $form, FormStateInterface $form_state, $gateway_id = NULL) {
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Pass validation to gateway.
    $function = $form_state->getValue(['gateway', 'configure form']) . '_validate';
    if (function_exists($function)) {
      $function($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $gateway = $form_state->getValue('gateway');
    // Remove unnecessary values.
    $form_state->cleanValues();
    $form_state->unsetValue('gateway');

    $this->config('sms.gateway.' . $gateway['identifier'])
      ->set('settings' , $form_state->getValues())
      ->save();
    drupal_set_message($this->t('The gateway settings have been saved.'));
    $form_state->setRedirect('sms.gateway_admin');
  }

  /**
   * Title callback fo the menu
   */
  public function getTitle($gateway_id) {
    $gateway = sms_gateways('gateway', $gateway_id);
    return sprintf('%s gateway', $gateway['name']);
  }
}
