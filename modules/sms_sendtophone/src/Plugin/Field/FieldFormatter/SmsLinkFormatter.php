<?php

namespace Drupal\sms_sendtophone\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Url;

/**
 * Defines a SMS field formatter.
 *
 * @FieldFormatter(
 *   id = "sms_link",
 *   label = @Translation("SMS Link"),
 *   field_types = {"text"}
 * )
 */
class SmsLinkFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();
    foreach ($items as $delta => $item) {
      $text = strip_tags($item->value);
      $element[$delta] = array(
        '#type' => 'markup',
        'text' => array(
          '#type' => 'markup',
          '#markup' => $text,
          '#prefix' => '<span class="sms-sendtophone-inline">',
          '#suffix' => '</span>',
        ),
        'link' => array(
          '#type' => 'link',
          '#prefix' => ' (',
          '#suffix' => ')',
          '#title' => $this->t('Send to phone'),
          '#url' => Url::fromRoute('sms_sendtophone.page', ['type' => 'field'], ['query' => ['text' => $text, 'destination' => \Drupal::destination()->get()]]),
          '#attributes' => array(
            'title' => $this->t('Send this text via SMS.'),
            'class' => 'sms-sendtophone',
          ),
        ),
      );
    }
    return $element;
  }

}
