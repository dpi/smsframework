<?php

/**
 * @file
 * Contains CarrierAdminForm class
 */

namespace Drupal\sms\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a configuration form for sms carriers.
 */
class CarrierAdminForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'sms_carriers_admin_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $carriers = sms_carriers();
    $form = array();
    foreach ($carriers as $id => $carrier) {
      $actions = array();
      $css_safe_id = str_replace('.', '-', $id);
  
      switch ($carrier['type']) {
        case SMS_CARRIER_DEFAULT:
          $storage = $this->t('Default');
          break;
        case SMS_CARRIER_OVERRIDDEN:
          $storage = $this->t('Overridden');
          break;
        case SMS_CARRIER_NORMAL:
        default:
          $storage = $this->t('Normal');
          break;
      }
      $form['status']['#tree'] = TRUE;
      if (!isset($carrier['status'])) {
        $carrier['status']=0;
      }
      $form['status'][$css_safe_id] = array(
        '#type' => 'checkbox',
        '#title' => $carrier['name'],
        '#description' => $storage,
        '#default_value' => $carrier['status'] == 1,
      );
  
      $form['domain'][$css_safe_id] = array(
        '#type' => 'markup',
        '#markup' => $id,
      );
  
      $actions[] = \Drupal::l($this->t('Edit'), Url::fromRoute('sms.carrier_edit', ['domain' => $id]));
  
      if ($carrier['type'] == SMS_CARRIER_OVERRIDDEN) {
        $actions[] = \Drupal::l($this->t('Revert'), Url::fromRoute('sms.carrier_delete', ['domain' => $id]));
      }
      elseif ($carrier['type'] == SMS_CARRIER_NORMAL) {
        $actions[] = \Drupal::l($this->t('Delete'), Url::fromRoute('sms.carrier_delete', ['domain' => $id]));
      }
  
      $form['actions'][$css_safe_id] = array(
        '#type' => 'markup',
        '#markup' => implode(' | ', $actions),
      );
    }
  
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
    );
  
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $enabled_carriers = array();
    foreach ($form_state['values']['status'] as $carrier => $status) {
      if ($status) {
        $enabled_carriers[] = str_replace('-', '.', $carrier);
      }
    }
    $this->config('sms.settings')->set('enabled_carriers', $enabled_carriers)->save();
    drupal_set_message($this->t('The configuration options have been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sms.settings'];
  }

}
