<?php

/**
 * @file
 * Contains \Drupal\sms_valid\RulesetListForm
 */

namespace Drupal\sms_valid;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\sms_valid\Entity\Ruleset;

/**
 * Class RulesetListController
 *
 * Validation rulesets list form
 * @todo This doesn't implement EntityListControllerInterface because of the form involved
 */
class RulesetListForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_valid_admin_rulesets_form';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $header = array(
      'prefix' => t('Prefix'),
      'name' => t('Name'),
      'iso2' => t('Country'),
      'qty_rules' => t('Qty Rules'),
      'in_out' => array(
        'data' => t('Allow messages'),
        'colspan' => 2,
      ),
      'operations' => t('Delete'),
    );

    $form['rulesets'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => array(),
    );

    foreach (Ruleset::loadMultiple() as $ruleset) {
      $prefix = $ruleset->prefix;
      $qty_rules = count($ruleset->rules);
      $rule_edit = ' (' . $this->l($this->t('edit'), new Url('sms_valid.ruleset_edit', ['sms_ruleset' => $prefix])) . ')';

      $form['rulesets'][$prefix]['prefix'] = array(
        '#type' => 'textfield',
        '#size' => 5,
        '#maxlength' => 5,
        '#disabled' => TRUE,
        '#value' => $ruleset->prefix,
      );
      $form['rulesets'][$prefix]['name'] = array('#markup' => $ruleset->name);
      $form['rulesets'][$prefix]['iso2'] = array('#markup' => $ruleset->iso2);
      $form['rulesets'][$prefix]['qty_rules'] = array('#markup' => $qty_rules . $rule_edit);
      $form['rulesets'][$prefix]['out'] = array(
        '#type' => 'checkbox',
        '#title' => 'Outbound',
        '#default_value' => sms_valid_ruleset_is_enabled($prefix, SMS_DIR_OUT),
      );
      $form['rulesets'][$prefix]['in'] = array(
        '#type' => 'checkbox',
        '#title' => 'Inbound',
        '#default_value' => sms_valid_ruleset_is_enabled($prefix, SMS_DIR_IN),
      );
      $form['rulesets'][$prefix]['delete'] = array(
        '#type' => 'checkbox',
        '#title' => 'Delete',
        '#default_value' => FALSE,
      );
    }

    $form['note'] = array(
      '#type' => 'item',
      '#value' => t('A ruleset is a number prefix with a set of deeper number prefixes, each with an allow/deny directive. For example, a ruleset prefix "64" and a rule like "21+" would allow a number like "64-21-123-4567". You can choose to have one big ruleset or you can split them into manageable rulesets by country, category, or whatever you decide.'),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save Changes'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('rulesets') as $prefix => $checkboxes) {
      // Delete immediately if specified.
      if ($checkboxes['delete']) {
        // @todo Add a confirm form to verify user intent to delete all these
        // rulesets before submitting.
        Ruleset::load($prefix)->delete();
      }
      else {
        sms_valid_ruleset_set_status($prefix, sms_dir($checkboxes['out'], $checkboxes['in']));
      }
    }
    drupal_set_message(t('Rulesets saved.'));
  }

}
