<?php

/**
 * Contains \Drupal\sms\Plugin\views\field\SmsMessageDirection
 */

namespace Drupal\sms\Plugin\views\field;

use Drupal\sms\Entity\SmsMessageInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\sms\Direction;

/**
 * Field handler to show SMS message direction
 *
 * @ViewsField("sms_message_direction")
 */
class SmsMessageDirection extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  function render(ResultRow $values) {
    switch ($this->getValue($values)) {
      case Direction::INCOMING:
        return $this->t('Incoming');
      case Direction::OUTGOING:
        return $this->t('Outgoing');
      default:
        return $this->t('Unknown direction');
    }
  }

}
