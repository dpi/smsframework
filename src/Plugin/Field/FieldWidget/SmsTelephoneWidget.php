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
          $element['value']['#description'] = $this->t('<strong>Phone number is verified.</strong> Modifying this phone number will remove verification.');
        }
        else {
          $element['value']['#disabled'] = TRUE;
          $expiration_seconds = 3600; // @fixme: hook up to config
          $expiration_date = $phone_verification->getCreatedTime() + $expiration_seconds;

          if (time() < $expiration_date) {
            /** @var \Drupal\Core\Datetime\DateFormatter $date_formatter */
            $date_formatter = \Drupal::service('date.formatter');
            $t_args = [
              '@time' => $date_formatter->formatTimeDiffUntil($expiration_date, [
                'granularity' => 2,
              ]),
            ];
            $element['value']['#description'] = $this->t('A validation code has been sent to this phone number. Go here to enter the code www.example.com. The code will expire if it is not verified in @time.', $t_args);
          }
          else {
            // This message displays if we are waiting for cron to delete
            // expired verification codes.
            $element['value']['#description'] = $this->t('Verification code expired. Try again later.');
          }
        }
      }
    }

    return $element;
  }

}
