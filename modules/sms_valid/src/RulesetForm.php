<?php
/**
 * @file
 * Definition of \Drupal\sms_valid\RulesetForm.
 */

namespace Drupal\sms_valid;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the ruleset create / edit form.
 */
class RulesetForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\sms_valid\Entity\Ruleset $ruleset */
    $ruleset = $this->entity;

    $form['refresh'] = array(
      '#type' => 'table',
      '#header' => array(),
    );

    // Ruleset selection area.
    $form['refresh']['title_row']['title'] = array(
        '#wrapper_attributes' => ['colspan' => 3],
        '#type' => 'item',
        '#markup' => $this->t('Choose a ruleset from the drop down box and click Refresh to update the ruleset form below.'),
        '#prefix' => '<strong>',
        '#suffix' => '</strong>',
    );

    $form['refresh']['refresh_row']['select_prefix'] = array(
      '#type' => 'select',
      '#options' => sms_valid_get_rulesets_for_form(),
      '#default_value' => $ruleset->prefix,
    );

    $form['refresh']['refresh_row']['select'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Refresh Editor (below)'),
      '#submit' => array(
        array($this, 'rulesetFormSelect'),
      ),
    );

    $form['refresh']['refresh_row'][] = array(
      '#markup' => '',
      '#wrapper_attributes' => ['width' => '80%'],
    );

    // Ruleset editor area.
    $form['ruleset'] = array(
      '#type' => 'fieldset',
      '#title' => 'Ruleset',
    );

    // If this is a new ruleset then this should be a textfield.
    $form['ruleset']['prefix'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#size' => 5,
      '#maxlength' => 5,
      // Why use '#value' key here instead of '#default_value' key, makes the resultant save() method below hacky.
      '#value' => $ruleset->prefix,
      '#description' => 'Should be 4 digits or less. Highest allowed prefix is 65535.',
    );

    $form['ruleset']['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#size' => 80,
      '#maxlength' => 200,
      '#default_value' => $ruleset->name,
    );

    // Display a proper country list if the countries_api module is loaded.
    if (function_exists('countries_api_get_array')) {
      $options[''] = '(none)';
      $options = array_merge($options, countries_api_get_array());
      $form['ruleset']['iso2'] = array(
        '#type' => 'select',
        '#title' => $this->t('Associated country (optional)'),
        '#options' => $options,
        '#default_value' => $ruleset->iso2,
      );
    }
    else {
      $form['ruleset']['iso2'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Country code (ISO 3166-1 alpha-2) (optional)'),
        '#size' => 2,
        '#maxlength' => 2,
        '#default_value' => $ruleset->iso2,
        '#description' => 'This would be a nice drop-down box if you had the Countries API module enabled.',
      );
    }

    $form['ruleset']['out'] = array(
      '#type' => 'checkbox',
      '#title' => 'Allow outbound communication',
      '#default_value' => ($ruleset->prefix) ? sms_valid_ruleset_is_enabled($ruleset->prefix, SMS_DIR_OUT) : false,
    );

    $form['ruleset']['in'] = array(
      '#type' => 'checkbox',
      '#title' => 'Allow inbound commmunication',
      '#default_value' => ($ruleset->prefix) ? sms_valid_ruleset_is_enabled($ruleset->prefix, SMS_DIR_IN) : false,
    );

    $form['ruleset']['rules'] = array(
      '#type' => 'textarea',
      '#title' => 'Rules',
      '#cols' => 80,
      '#rows' => 15,
      '#default_value' => sms_valid_rules_to_text($ruleset->rules),
      '#description' => $this->t('One rule per line. Enter a number prefix (any length), not including the ruleset prefix.<br />Any prefix with a "-" at the end signifies an expicit deny.<br />Any prefix with a "+" at the end signifies an explicit allow.<br />All other rules are ignored.<br />Default is to deny any numbers that do not match.<br />Comments must be prefixed with a hash (#). You may place comments in-line only.<br />See the guide at %url', array('%url' => 'http://moo0.net/smsframework/?q=node/19')),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save Ruleset');
    return $actions;
  }

  /**
   * Callback to load a different ruleset from the current one
   *
   * @param $form
   * @param $form_state
   */
  function rulesetFormSelect(&$form, FormStateInterface $form_state) {
    $form_state->setRedirect('sms_valid.ruleset_edit', ['sms_ruleset' => $form_state->getValue(['refresh','refresh_row','select_prefix'])]);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $ruleset = $this->entity;
    $ruleset->rules = sms_valid_text_to_rules($form_state->getValue('rules'));
    $ruleset->dirs_enabled = sms_dir($form_state->getValue('out'), $form_state->getValue('in'));
    // See comment at $form['ruleset']['prefix'] in form() method call above
    $ruleset->prefix = $form_state->getUserInput()['prefix'];

    $ruleset->save();
    drupal_set_message($this->t('Ruleset saved.'));
    $form_state->setRedirect('sms_valid.ruleset_list');
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, FormStateInterface $form_state) {
    // Redirect to confirm form.
    $form_state->setRedirect('sms_valid.ruleset_delete', ['sms_ruleset' => $this->entity->prefix]);
  }

}
