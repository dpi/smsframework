<?php

/**
 * @file
 * Contains \Drupal\sms_user\Plugin\views\field\SmsStatus
 */

namespace Drupal\sms_user\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Views field handler to display the sms number status.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("sms_status")
 */
class SmsStatus extends FieldPluginBase {
  /**
   * {@inheritdoc}
   */
  function render(ResultRow $values) {
    $status = $this->getValue($values);
    $status_defined = array(
      SMS_USER_PENDING => t('Pending'),
      SMS_USER_CONFIRMED => t('Confirmed'),
    );
    return $status_defined[$status];
  }
}