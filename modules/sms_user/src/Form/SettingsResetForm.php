<?php

/**
 * @file
 * Contains SettingsResetForm class
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
class SettingsResetForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'sms_user_settings_reset_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $account = NULL) {
    if (!isset($account)) {
      $account = $this->currentUser();
    }
    $form['uid'] = array(
      '#type' => 'hidden',
      '#value' => $account->id(),
    );
    $form['sms_user']['number'] = array(
      '#type' => 'item',
      '#title' => t('Your mobile phone number'),
      '#markup' => $account->sms_user['number'],
      '#description' => t('Your mobile phone number has been confirmed.'),
    );
  
    $form['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Delete & start over'),
      '#access' => $account->hasPermission('edit own sms number'),
    );
  
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = User::load($form_state->getValue('uid'));
    if (sms_user_delete($account->id())) {
      if (\Drupal::moduleHandler()->moduleExists('rules')) {
        rules_invoke_event('sms_user_removed', $account);
      }
      // Save account changes to trigger reload.
      unset($account->sms_user);
      $account->save();
      drupal_set_message(t('Your mobile information has been removed'), 'status');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

}
