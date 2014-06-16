<?php
/**
 * @file
 * SMS Framework Message Tracking feature module: views
 *
 * @package sms
 * @subpackage sms_track
 */
/**
 * Contains \Drupal\sms_track\Plugin\views\field\SmsGateway
 */

namespace Drupal\sms_track\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler for SMS Gateway
 *
 * @ViewsField("sms_gateway")
 */
class SmsGateway extends FieldPluginBase {
  /**
   * {@inheritdoc}
   */
  function render(ResultRow $values) {
    $options_z = $this->getValue($values); // {$this->field_alias};
    if (!empty($options_z)) {
      $options = unserialize($options_z);
      if (is_array($options) && array_key_exists('gateway_id', $options)) {
        return $options['gateway_id'];
      }
      else {
        return t('n/a');
      }
    }
  }
}
