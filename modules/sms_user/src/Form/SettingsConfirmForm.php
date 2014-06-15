<?php

/**
 * @file
 * Contains SettingsConfirmForm class
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
class SettingsConfirmForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'sms_user_settings_confirm_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $account=NULL) {
    if (!isset($account)) {
      $account = $this->currentUser();
    }
    $form = parent::buildForm($form, $form_state);
    $form['uid'] = array(
      '#type' => 'hidden',
      '#value' => $account->id(),
    );
    $form['number'] = array(
      '#type' => 'item',
      '#title' => t('Mobile phone number'),
      '#markup' => $account->sms_user['number'],
    );
    $form['confirm_code'] = array(
      '#type' => 'textfield',
      '#title' => t('Confirmation code'),
      '#description' => t('Enter the confirmation code sent by SMS to your mobile phone.'),
      '#size' => 4,
      '#maxlength' => 4,
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Confirm number'),
    );
    $form['actions']['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Delete & start over'),
      '#access' => user_access('edit own sms number'),
    );
    $form['actions']['confirm'] = array(
      '#type' => 'submit',
      '#value' => t('Confirm without code'),
      '#access' => user_access('administer site'),
    );
  
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    if ($form_state['triggering_element']['#value'] == $form_state['values']['submit']) {
      $account = User::load($form_state['values']['uid']);
      if ($form_state['values']['confirm_code'] != $account->sms_user['code']) {
        form_set_error('confirm_code', t('The confirmation code is invalid.'));
      }
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $account = User::load($form_state['values']['uid']);
    if ($form_state['triggering_element']['#value'] == $form_state['values']['reset']) {
      sms_user_delete($account->id());
    }
    else {
      $account->sms_user['status'] = SMS_USER_CONFIRMED;
      $account->save();
      // If the rule module is installed, fire rules
      if (\Drupal::moduleHandler()->moduleExists('rules')) {
        rules_invoke_event('sms_user_validated', $account);
      }
    }
  }
}