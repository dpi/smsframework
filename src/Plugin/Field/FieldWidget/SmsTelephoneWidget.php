<?php

/**
 * @file
 * Contains \Drupal\sms\Plugin\Field\FieldWidget\SmsTelephoneWidget.
 */

namespace Drupal\sms\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\telephone\Plugin\Field\FieldWidget\TelephoneDefaultWidget;

/**
 * Plugin implementation of the 'sms_telephone' widget.
 *
 * @FieldWidget(
 *   id = "sms_telephone",
 *   label = @Translation("SMS Framework Telephone"),
 *   field_types = {
 *     "telephone"
 *   }
 * )
 */
class SmsTelephoneWidget extends TelephoneDefaultWidget {

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // @field value udpated hook

    return $element;
  }

}
