<?php

/**
 * @file
 * Contains SettingsSleepForm class
 */

/**
 * Provides a form for user mobile settings
 */
namespace Drupal\sms_user\Form;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;

/**
 * @todo the sms_user appendage to $user object needs to be implemented as a
 * field in D8
 */
class SettingsSleepForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'sms_user_settings_sleep_form';
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
    $form['sleep'] = array(
      '#type' => 'fieldset',
      '#title' => t('Sleep Time'),
      '#collapsible' => TRUE,
    );
  
    $form['sleep']['sleep_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Disable messages between these hours'),
      '#description' => t('If enabled, you will not receive messages between the specified hours.'),
      '#default_value' => isset($account->sms_user['sleep_enabled']) ? $account->sms_user['sleep_enabled'] : NULL,
    );
  
    // Determine whether to use the 24-hour or 12-hour clock based on site settings
    if (strpos(DateFormat::load('short')->getPattern(), 'g')) {
      $format = 'g A';
    }
    else {
      $format = 'H:00';
    }
    // Build the list of options based on format
    $hour = 0; $options = array();
    while ($hour < 24) {
      $options[$hour] = date($format, mktime($hour));
      $hour++;
    }
  
    $form['sleep']['sleep_start_time'] = array(
      '#type' => 'select',
      '#multiple' => FALSE,
      '#options' => $options,
      '#default_value' => isset($account->sms_user['sleep_start_time']) ? $account->sms_user['sleep_start_time'] : NULL,
    );
  
    $form['sleep']['sleep_end_time'] = array(
      '#type' => 'select',
      '#multiple' => FALSE,
      '#options' => $options,
      '#default_value' => isset($account->sms_user['sleep_end_time']) ? $account->sms_user['sleep_end_time'] : NULL,
    );
  
    $form['sleep']['save'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );
  
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = User::load($form_state->getValue('uid'));
    $account->sms_user['sleep_enabled'] = $form_state->getValue('sleep_enabled');
    $account->sms_user['sleep_start_time'] = $form_state->getValue('sleep_start_time');
    $account->sms_user['sleep_end_time'] = $form_state->getValue('sleep_end_time');
    $account->save();
    drupal_set_message(t('The changes have been saved.'), 'status');
  }
}