<?php

/**
 * @file
 * Contains GatewayDefaultForm class
 */

namespace Drupal\sms\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $gateways = sms_gateways();
    $default = sms_default_gateway_id();

    $form['gateways'] = array(
      '#type' => 'table',
      '#header' => array(
        $this->t('Default'),
        $this->t('Name'),
        array(
          'data' => t('Operations'),
          'colspan' => 1,
        )
      ),
      '#attributes' => array(
        'id' => 'table-' . $this->getFormID(),
      ),
    );
    foreach ($gateways as $identifier => $gateway) {
      $form['gateways'][$identifier] = array(
        'default' => [
          '#name' => 'default',
          '#type' => 'radio',
          '#default_value' => $default,
          '#return_value' => $identifier,
        ],
        'name' => ['#markup' => String::checkPlain($gateway['name'])],
      );
      if (isset($gateway['configure form']) && function_exists($gateway['configure form'] )) {
        $form['gateways'][$identifier]['configure'] = ['#markup' => $this->l($this->t('configure'), Url::fromRoute('sms.gateway_config', ['gateway_id' => $identifier]))];
      }
      else {
        $form['gateways'][$identifier]['configure'] = array('#markup' => $this->t('No configuration options'));
      }
    }
    $form['actions']['submit']['#value'] = $this->t('Set default gateway');
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('default', $form_state->getUserInput()['default']);
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Process form submission to set the default gateway.
    $default = $form_state->getValue('default');
    if (sms_gateways('name', $default)) {
      drupal_set_message($this->t('Default gateway updated.'));

      $this->config('sms.settings')
        ->set('default_gateway', $default)
        ->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sms.settings'];
  }

}
