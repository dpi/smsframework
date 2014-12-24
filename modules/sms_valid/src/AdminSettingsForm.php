<?php

/**
 * @file
 * Contains Drupal\sms_valid\AdminSettingsForm
 *
 * SMS Framework core module: Admin settings form functions
 */

namespace Drupal\sms_valid;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Validation settings form
 *
 * @param $prefix
 *   Default country code. This should not be used.
 *
 * @ingroup forms
 */
class AdminSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_valid_admin_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sms_valid.settings');
    $use_rulesets = $config->get('use_rulesets');
    $use_global_ruleset = $config->get('use_global_ruleset');

    // Mode selector.
    if ($use_rulesets) {
      $mode = ($use_global_ruleset) ? 2 : 1;
    }
    else {
      $mode = 0;
    }
    $form['mode'] = array(
      '#type' => 'radios',
      '#title' => 'Number validation',
      '#default_value' => $mode,
      '#options' => array(
        0 => 'No rulesets. Only use validation hooks implemented by gateway or other modules. [default]',
        1 => 'Use prefix-based validation rulesets.',
        2 => 'Use one ruleset for all numbers.',
      ),
      '#description' => t('Note that this will revert to the default option when the SMS Validation module is disabled.'),
    );

    // Global ruleset section.
    $form['global'] = array(
      '#type' => 'fieldset',
      '#title' => 'Global ruleset selection',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    $form['global']['note'] = array(
      '#type' => 'item',
      '#value' => 'Identifies the ruleset that will be used for all numbers if the "Use one ruleset" option is selected.',
    );

    $form['global']['global_ruleset'] = array(
      '#type' => 'select',
      '#title' => t('Ruleset to use as the global ruleset'),
      '#options' => sms_valid_get_rulesets_for_form(),
      '#default_value' => $config->get('global_ruleset'),
      // Implement default of $prefix.
    );

    // Local number ruleset section.
    $form['local'] = array(
      '#type' => 'fieldset',
      '#title' => 'Local number detection and handling',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    $form['local']['note'] = array(
      '#type' => 'item',
      '#value' => 'You can use this to tell the number validation function that any number with this prefix should be considered a local number. The prefix will be stripped away and the number will be validated against the selected ruleset.',
    );

    $form['local']['local_number_prefix'] = array(
      '#type' => 'textfield',
      '#title' => t('Prefix that identifies a local number'),
      '#size' => 8,
      '#maxlength' => 20,
      '#default_value' => $config->get('local_number_prefix'),
      '#description' => 'Set to blank to disable local number identification.',
      '#disabled' => ($use_global_ruleset) ? TRUE : FALSE,
    );

    $form['local']['local_number_ruleset'] = array(
      '#type' => 'select',
      '#title' => t('Default ruleset to try for local numbers'),
      '#options' => sms_valid_get_rulesets_for_form(),
      '#default_value' => $config->get('local_number_ruleset'),
      // Implement $prefix as default.
      '#description' => 'This identifies the default ruleset that will be used for local numbers.',
      '#disabled' => ($use_global_ruleset) ? TRUE : FALSE,
    );

    // Last resort ruleset section.
    $form['last'] = array(
      '#type' => 'fieldset',
      '#title' => 'Last resort for undetected ruleset prefixes',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    $form['last']['note'] = array(
      '#type' => 'item',
      '#value' => 'If the number validation function cannot find a ruleset to use (ie. it cannot find a prefix match) you can tell it to try a last resort ruleset.<br /><strong>WARNING!</strong> Please be very careful when using this option to ensure that you do not have unexpected behavior in your rulesets.',
    );

    $form['last']['last_resort_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use last resort ruleset'),
      '#default_value' => $config->get('last_resort_enabled'),
      '#disabled' => ($use_global_ruleset) ? TRUE : FALSE,
    );

    $form['last']['last_resort_ruleset'] = array(
      '#type' => 'select',
      '#title' => t('Ruleset to try if the ruleset prefix cannot be identified from the number'),
      '#options' => sms_valid_get_rulesets_for_form(),
      '#default_value' => $config->get('last_resort_ruleset'),
      // Implement $prefix as default
      '#description' => 'This only works if you have selected the checkbox above.',
      '#disabled' => ($use_global_ruleset) ? TRUE : FALSE,
    );

    $form['defaults_save'] = array(
      '#type' => 'submit',
      '#value' => t('Save settings'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mode = $form_state->getValue('mode');
    $use_rulesets = ($mode) ? TRUE : FALSE;
    $use_global_ruleset = ($mode == 2) ? TRUE : FALSE;
    $form_state->cleanValues();
    $values = array(
        'use_rulesets' => $use_rulesets,
        'use_global_ruleset' => $use_global_ruleset,
      ) + $form_state->getValues();
    unset($values['note'], $values['mode']);
    // Save to config.
    $this->config('sms_valid.settings')->setData($values)->save();
    drupal_set_message(t('Settings saved.'));
  }
}
