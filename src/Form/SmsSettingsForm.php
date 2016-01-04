<?php

/**
 * @file
 * Contains \Drupal\sms\Form\SmsSettingsForm
 */

namespace Drupal\sms\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sms\Entity\SmsGateway;

/**
 * Provides a form for SMS settings.
 */
class SmsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'sms_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    /** @var \Drupal\sms\Entity\SmsGateway[] $gateways */
    $gateways = SmsGateway::loadMultiple();
    $default = $this->config('sms.settings')->get('default_gateway');
    $options = [];
    foreach ($gateways as $name => $gateway) {
      $options[$name] = $gateway->label();
    }

    $form['default_gateway'] = [
      '#type' => 'select',
      '#title' => $this->t('Default gateway'),
      '#options' => $options,
      '#default_value' => $default,
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Set the default gateway.
    $this->config('sms.settings')
      ->set('default_gateway', $form_state->getValue('default_gateway'))
      ->save();
    $form_state->setRedirect('sms.admin');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sms.settings'];
  }

}
