<?php
/**
 * Contains \Drupal\sms_track\Plugin\views\field\SmsLocalNumber
 */

namespace Drupal\sms_track\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler for SMS Local Number
 *
 * @ViewsField("sms_local_number")
 */
class SmsLocalNumber extends FieldPluginBase {
  /**
   * {@inheritdoc}
   */
  function render(ResultRow $values) {
    $options_z = $this->getValue($values);
    if (!empty($options_z)) {
      $options = unserialize($options_z);
      if (is_array($options)) {
        if (array_key_exists('receiver', $options)) {
          return $options['receiver'];
        }
        elseif (array_key_exists('sender', $options)) {
          return $options['sender'];
        }
      }
      return t('n/a');
    }
  }
}
