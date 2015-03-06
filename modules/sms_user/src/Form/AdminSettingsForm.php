<?php

/**
 * @file
 * Contains AdminSettingsForm class
 */

namespace Drupal\sms_user\Form;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Provides a configuration form for sms carriers.
 */
class AdminSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'sms_user_admin_settings';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $op = NULL, $domain = NULL) {
    $config = $this->config('sms_user.settings');
    $form['registration_form'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Show mobile fields during user registration'),
      '#description' => $this->t('Specify if the site should collect mobile information during registration.'),
      '#options' => array(
        $this->t('Disabled'),
        $this->t('Optional'),
        $this->t('Required')
      ),
      '#default_value' => $config->get('registration_form'),
    );
  
    $form['confirmation_message'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Confirmation message format'),
      '#default_value' => $config->get('confirmation_message'),
      '#description' => $this->t('Specify the format for confirmation messages. Keep this as short as possible.'),
      '#size' => 140,
      '#maxlength' => 255,
    );
  
    // Add the token help to a collapsed fieldset at the end of the configuration page.
    $form['tokens']['token_help'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Available Tokens List'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['tokens']['token_help']['content'] = array(
      '#theme' => 'token_tree',
      '#token_types' => array('sms_user'),
    );
    /*
    $form['tokens'] = array(
      '#type' => 'fieldset',
      '#title' => t('Available replacement patterns'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
  
    $form['tokens']['content']['#value'] = theme('token_tree', array('token_types' => array('sms_user')));
    */

    // Sleep settings.
    $form['sleep'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Global Sleep Settings'),
      '#description' => $this->t('Enable Sleep hours. Start and End times are global. Users may override these settings on an individual basis. If Start and End time are both 0:00, only individual overrides will be taken into account.'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
  
    $form['sleep']['enable_sleep'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable sleep hours'),
      '#description' => $this->t('If checked, users will be able to specifiy hours during which they will not receive messages from the site.'),
      '#default_value' => $config->get('enable_sleep'),
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
        '#title' => $this->t('Start time'),
        '#type' => 'select',
        '#multiple' => FALSE,
        '#options' => $options,
        '#default_value' => $config->get('sleep_start_time'),
    );
  
    $form['sleep']['sleep_end_time'] = array(
        '#title' => $this->t('End time'),
        '#type' => 'select',
        '#multiple' => FALSE,
        '#options' => $options,
        '#default_value' => $config->get('sleep_end_time'),
    );

    // SMS User opt-out settings.
    $form['opt_out'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('User Opt Out Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['opt_out']['allow_opt_out'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to opt-out of receiving messages from this site'),
      '#description' => $this->t('If checked, users will be able to opt out of receiving messages from the site.'),
      '#default_value' => $config->get('allow_opt_out'),
    );

    // Registration settings.
    $form['registration'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Registration settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['registration']['registration_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable registration'),
      '#default_value' => $config->get('registration_enabled'),
      '#description' => $this->t('If selected, users can create user accounts via SMS.'),
    );
    $form['registration']['allow_password'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow password creation'),
      '#default_value' => $config->get('allow_password'),
      '#description' => $this->t('If selected, the user will be allowed to include a password in their registration request -- the password will be the first word in the first line of the request.'),
    );
    $form['registration']['new_account_message'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('New user message'),
      '#default_value' => $config->get('new_account_message'),
      '#description' => $this->t('The message that will be sent to newly registered users.  Leave empty for no message.'),
    );
  
    // Add the token help to a collapsed fieldset at the end of the registration page.
    $form['registration']['tokens']['token_help'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Available Tokens List'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['registration']['tokens']['token_help']['content'] = array(
      '#theme' => 'token_tree',
      '#token_types' => array('sms_user'),
    );
    /*
    $form['registration']['tokens'] = array(
      '#type' => 'fieldset',
      '#title' => t('Available replacement patterns'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
  
    $form['registration']['tokens']['content']['#value'] = theme('token_tree', array('token_types' => array('sms_user')));
    */
    $form['max_chars'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('max_chars'),
      '#size' => 3,
      '#title' => $this->t('Maximum number of chars for SMS sending using actions'),
    );
          
    // Get system setting form
    return parent::buildForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Clean up the form_state values and save to config
    $form_state->cleanValues();
    $this->config('sms_user.settings')->setData($form_state->getValues())->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sms_user.settings'];
  }

}
