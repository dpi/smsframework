<?php

/**
 * @file
 * Contains \Drupal\sms_user\Plugin\views\field\SmsNumber
 */

namespace Drupal\sms_user\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * A handler to provide displays for users' mobile phone
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("sms_number")
 */
class SmsNumber extends FieldPluginBase {
  /**
   * {@inheritdoc}
   */
  function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->sanitizeValue($value);
  }
}