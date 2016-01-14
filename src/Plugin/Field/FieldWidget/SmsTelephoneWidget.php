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

    if (isset($items[$delta]->value)) {
      /** @var \Drupal\sms\PhoneNumberProviderInterface $phone_number_provider */
      $phone_number_provider = \Drupal::service('sms.phone_number');
      $phone_verification = $phone_number_provider
        ->getPhoneVerification($items->getEntity(), $items[$delta]->value);

      if ($phone_verification) {
        if ($phone_verification->getStatus()) {
          $element['#description'] = $this->t('<strong>Phone number is verified.</strong> Modifying this phone number will remove verification.');
        }
        else {
          $expiration_seconds = 3600; // @fixme: hook up to config
          $seconds = ($phone_verification->getCreatedTime() + $expiration_seconds) - time();
          $t_args = [
            '@minutes' => floor($seconds / 60)
          ];

          if ($seconds > 0) {
            $element['#description'] = $this->t('A validation code has been sent to this phone number. Go here to enter the code www.example.com. The code will expire if it is not verified within the next @minutes minutes', $t_args);
            $element['#disabled'] = TRUE;
          }
          else {
            $element['#description'] = $this->t('Verification code has expired.');
          }
        }
      }
    }

    $element['value'] = $element + array(
        '#type' => 'tel',
        '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
        '#placeholder' => $this->getSetting('placeholder'),
      );

    return $element;
  }

}
