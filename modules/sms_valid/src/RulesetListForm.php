<?php

/**
 * @file
 * Contains \Drupal\sms_valid\RulesetListForm
 */

namespace Drupal\sms_valid;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;

/**
 * Class RulesetListController
 *
 * Validation rulesets list form
 * @todo This doesn't implement EntityListControllerInterface because of the form involved
 */
class RulesetListForm extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'sms_valid_admin_rulesets_form';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state)
  {
    $rulesets = sms_valid_get_all_rulesets();

    $form['note'] = array(
      '#type' => 'item',
      '#value' => t('A ruleset is a number prefix with a set of deeper number prefixes, each with an allow/deny directive. For example, a ruleset prefix "64" and a rule like "21+" would allow a number like "64-21-123-4567". You can choose to have one big ruleset or you can split them into manageable rulesets by country, category, or whatever you decide.'),
    );

    foreach ($rulesets as $r) {
      $prefix = $r->prefix;
      $qty_rules = count($r->rules);
      $rule_edit = ' (' . l(t('edit'), "admin/config/smsframework/validation/ruleset/$prefix") . ')';

      $form[$prefix]['prefix'] = array(
        '#type' => 'textfield',
        '#size' => 5,
        '#maxlength' => 5,
        '#disabled' => TRUE,
        '#value' => $r->prefix,
      );
      $form[$prefix]['name'] = array('#markup' => $r->name);
      $form[$prefix]['iso2'] = array('#markup' => $r->iso2);
      $form[$prefix]['qty_rules'] = array('#markup' => $qty_rules . $rule_edit);
      $form[$prefix][$prefix . '_out'] = array(
        '#type' => 'checkbox',
        '#title' => 'Outbound',
        '#default_value' => sms_valid_ruleset_is_enabled($prefix, SMS_DIR_OUT),
      );
      $form[$prefix][$prefix . '_in'] = array(
        '#type' => 'checkbox',
        '#title' => 'Inbound',
        '#default_value' => sms_valid_ruleset_is_enabled($prefix, SMS_DIR_IN),
      );
      $form[$prefix][$prefix . '_delete'] = array(
        '#type' => 'checkbox',
        '#title' => 'Delete',
        '#default_value' => FALSE,
      );
    }

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
  public function submitForm(array &$form, array &$form_state)
  {
    foreach ($form_state['values'] as $key => $element) {
      // @todo Nasty hack to get these values
      $items = explode('_', $key);
      if (count($items) == 2) {
        // Just run once for each prefix
        if ($items[1] == 'out') {
          $prefix = $items[0];

          // Handle deletes
          $delete = $form_state['values'][$prefix . '_delete'];
          if ($delete) {
            // Redirect to delete confirm form
            // @todo Add a confirm form to verify user intent to delete all
            // these rulesets before submitting.
            sms_valid_delete_ruleset($prefix);
          }
          else {
            $out = $form_state['values'][$prefix . '_out'];
            $in = $form_state['values'][$prefix . '_in'];
            sms_valid_ruleset_set_status($prefix, sms_dir($out, $in));
          }
        }
      }
    }
    drupal_set_message(t('Rulesets saved.'));
  }
}


