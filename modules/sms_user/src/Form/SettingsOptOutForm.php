<?php

/**
 * @file
 * Contains SettingsSleepForm class
 */

/**
 * Provides a form for user sms opt out settings.
 */
namespace Drupal\sms_user\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;

/**
 * @todo the sms_user appendage to $user object needs to be implemented as a
 * field in D8
 * Form constructor for the user opt out form.
 *
 * @param object $account
 *   The user account object.
 *
 * @see sms_user_opt_out_form_submit()
 *
 * @ingroup forms
 */
class SettingsOptOutForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'sms_user_opt_out_form';
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

    $form['opt_out'] = array(
      '#type' => 'fieldset',
      '#title' => t('Opt Out'),
      '#collapsible' => TRUE,
    );

    $form['opt_out']['opted_out'] = array(
      '#type' => 'checkbox',
      '#title' => t('Opt out of sms messages from this site.'),
      '#description' => t('If enabled, you will not receive messages from this site.'),
      '#default_value' => isset($account->sms_user['opted_out']) ? $account->sms_user['opted_out'] : NULL,
    );

    $form['opt_out']['set'] = array(
      '#type' => 'submit',
      '#value' => t('Set'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = User::load($form_state->getValue('uid'));
    $account->sms_user['opted_out'] = $form_state->getValue('opted_out');
    $account->save();
    drupal_set_message(t('The changes have been saved.'), 'status');
  }
}