<?php

/**
 * @file
 * Contains \Drupal\sms_user\Plugin\views\filter\SmsStatus
 */

namespace Drupal\sms_user\Plugin\views\filter;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Views handler to filter users by sms phone number validation status.
 *
 * @PluginID("sms_status")
 */
class SmsStatus extends InOperator {
  /**
   * {@inheritdoc}
   */
  function getValueOptions() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Status');
      $this->value_options = array(
        SMS_USER_PENDING => t('Pending'),
        SMS_USER_CONFIRMED => t('Confirmed'),
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  function valueForm(&$form, &$form_state) {
    // Only values from that defined ones
    parent::valueForm($form, $form_state);
    $form['value']['#type'] = 'select';
  }
}