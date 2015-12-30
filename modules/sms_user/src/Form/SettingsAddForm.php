<?php

/**
 * @file
 * Contains SettingsAddForm class
 */

/**
 * Provides a form for user mobile settings
 */
namespace Drupal\sms_user\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;

/**
 * @todo the sms_user appendage to $user object needs to be implemented as a
 * field in D8
 */
class SettingsAddForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'sms_user_settings_add_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $account = NULL) {
    if (!isset($account)) {
      $account = $this->currentUser();
    }

    // Use SMS Send form for 'number' and 'gateway' fields.
    if ($send_form = sms_send_form(TRUE)) {
      $form = parent::buildForm($send_form, $form_state);
    }

    // Add element validation for number.
    $form['number']['#element_validate'][] = 'sms_user_validate_number_element';
    $form['uid'] = array(
      '#type' => 'hidden',
      '#value' => $account->id(),
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Confirm number'),
    );
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = User::load($form_state->getValue('uid'));
    sms_user_send_confirmation($account, $form_state->getValue('number'), (array) $form_state->getValue('gateway'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

}
