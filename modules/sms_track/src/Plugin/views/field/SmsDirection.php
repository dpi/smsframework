<?php
/**
 * Contains \Drupal\sms_track\Plugin\views\field\SmsDirection
 */

namespace Drupal\sms_track\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler for SMS Direction
 *
 * @ViewsField("sms_direction")
 */
class SmsDirection extends FieldPluginBase {
  /**
   * {@inheritdoc}
   */
  function render(ResultRow $values) {
    $value = $this->getValue($values);
    switch ($value) {
      case 0:
        return t('Out');
      case 1:
        return t('In');
    }
    // else
    return $value;
  }
}
