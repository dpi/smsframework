<?php

/**
 * @file
 * Contains CarrierEditForm class
 */

namespace Drupal\sms\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Provides a configuration form for sms carriers.
 */
class CarrierEditForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'sms_carriers_edit_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $domain = NULL) {
    $carrier = sms_carriers($domain);
    if (!isset($carrier['domain'])) {
      $carrier['domain']=NULL;
    }
    if (!isset($carrier['name'])) {
      $carrier['name']=NULL;
    }
    $form['carrier'] = array(
      '#type' => 'value',
      '#value' => $carrier['domain'],
    );

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $carrier['name'],
      '#required' => TRUE,
    );

    $form['domain'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Domain'),
      '#default_value' => $carrier['domain'],
      '#required' => TRUE,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $carriers = sms_carriers();
    if ($form_state['values']['domain'] != $form_state['values']['carrier']) {
      foreach ($carriers as $domain => $carrier) {
        if ($domain == $form_state['values']['domain']) {
          form_set_error('', $this->t('Domain must be unique.'));
        }
      }
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $carrier = array(
      'name' => $form_state['values']['name'],
      'domain' => $form_state['values']['domain'],
    );
    carrier_save($form_state['values']['carrier'], $carrier);
    drupal_set_message($this->t('The carrier has been saved.'));
    $form_state['redirect'] = 'admin/config/smsframework/carriers';
  }
}
