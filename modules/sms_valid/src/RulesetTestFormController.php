<?php
/**
 * @file
 * Contains \Drupal\sms_valid\RulesetTestFormController
 */

namespace Drupal\sms_valid;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Validation number test form
 */
class RulesetTestFormController extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_valid_admin_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['test_mode'] = array(
      '#type' => 'radios',
      '#title' => 'Validator(s)',
      '#default_value' => \Drupal::state()->get('sms_valid.test_mode', 0),
      '#options' => array(
        0 => 'Test against rulesets only. Verbose feedback.',
        1 => 'Test against the main validation function.',
      ),
      '#description' => 'The main validation function includes rulesets (if enabled), the active gateway module and other modules that implement number validation hooks.',
    );

    $form['number'] = array(
      '#type' => 'textfield',
      '#title' => t('Number'),
      '#size' => 20,
      '#maxlength' => 30,
      '#default_value' => \Drupal::state()
        ->get('sms_valid.test_last_number', ''),
      '#description' => 'Examples: 64211234567, 021-123-4567, 21.123.4567<br />Number will be validated using all configured settings and rulesets.',
    );

    $form['validate'] = array(
      '#type' => 'submit',
      '#value' => t('Validate Number'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $number = $form_state->getValue('number');
    drupal_set_message($this->t('You entered: @number', array('@number' => $number)));
    \Drupal::state()->set('sms_valid.test_last_number', $number);

    $test_mode = $form_state->getValue('test_mode');
    \Drupal::state()->set('sms_valid.test_mode', $test_mode);

    if (!\Drupal::config('sms_valid.settings')->get('use_rulesets')) {
      drupal_set_message($this->t('Note: Rulesets are disabled.'));
    }

    $pass = true;
    switch ($test_mode) {
      // Test only against rulesets.
      case 0:
        $options = array('test' => true);
        $result = sms_valid_validate($number, $options);
        $pass = $result['pass'];
        $log_msg = implode("<br />", $result['log']);
        drupal_set_message(Html::escape($log_msg));
        break;
      // Test main validation function.
      case 1:
        $error = sms_validate_number($number);
        if ($error) {
          drupal_set_message(t('Error message from validation function: %error', array('%error' => Xss::filter(implode("<br />", $error)))));
        }
        $pass = !$error;
        break;
    }

    if ($pass) {
      drupal_set_message(t('Validation succeeded and returned number %number.', array('%number' => $number)));
    }
    else {
      $form_state->setErrorByName('number', 'Validation failed.');
    }
  }

}
