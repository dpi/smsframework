<?php

/**
 * @file
 * Contains \Drupal\sms_user\Plugin\views\filter\SmsStatus
 */

namespace Drupal\sms_user\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Views handler to filter users by sms phone number validation status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("sms_status")
 */
class SmsStatus extends InOperator {
  /**
   * {@inheritdoc}
   */
  function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $this->valueTitle = t('Status');
      $this->valueOptions = array(
        SMS_USER_PENDING => t('Pending'),
        SMS_USER_CONFIRMED => t('Confirmed'),
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  function valueForm(&$form, FormStateInterface $form_state) {
    // Only values from that defined ones
    parent::valueForm($form, $form_state);
    $form['value']['#type'] = 'select';
  }
}