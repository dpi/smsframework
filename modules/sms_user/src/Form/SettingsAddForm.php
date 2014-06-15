<?php

/**
 * @file
 * Contains SettingsAddForm class
 */

/**
 * Provides a form for user mobile settings
 */
namespace Drupal\sms_user\Form;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\ConfigFormBase;
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
  public function buildForm(array $form, array &$form_state, $account=NULL) {
    if (!isset($account)) {
      $account = $this->currentUser();
    }
    $form = parent::buildForm(sms_send_form(TRUE), $form_state);
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
   *
   * Validate the users number.
   */
  public function validateForm(array &$form, array &$form_state) {
    if ($error = sms_user_validate_number($form_state['values']['number'])) {
      if (is_array($error)) {
        \Drupal::formBuilder()->setErrorByName('number', $form_state, $this->t("This is not a valid number on this website."));
      }
      else {
        \Drupal::formBuilder()->setErrorByName('number', $form_state, $error);
      }
    }
  
    if (empty($form_state['values']['gateway'])) {
      $form_state['values']['gateway'] = array();
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $account = User::load($form_state['values']['uid']);
    sms_user_send_confirmation($account, $form_state['values']['number'], $form_state['values']['gateway']);
  }
}