<?php

namespace Drupal\sms\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\telephone\Plugin\Field\FieldWidget\TelephoneDefaultWidget;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\sms\Exception\PhoneNumberSettingsException;

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

  use UrlGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    /** @var \Drupal\sms\Provider\PhoneNumberVerificationInterface $phone_number_verification_provider */
    $phone_number_verification_provider = \Drupal::service('sms.phone_number.verification');
    try {
      $config = $phone_number_verification_provider->getPhoneNumberSettingsForEntity($items->getEntity());
    }
    catch (PhoneNumberSettingsException $e) {
      return $element;
    }

    /** @var \Drupal\Core\Datetime\DateFormatter $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');
    $current_time = \Drupal::request()->server->get('REQUEST_TIME');

    $t_args['@url'] = $this->url('sms.phone.verify');
    $lifetime = $config->getVerificationCodeLifetime() ?: 0;

    if (isset($items[$delta]->value)) {
      $phone_verification = $phone_number_verification_provider
        ->getPhoneVerificationByEntity($items->getEntity(), $items[$delta]->value);

      if ($phone_verification) {
        if ($phone_verification->getStatus()) {
          $element['value']['#description'] = $this->t('This phone number is verified. <strong>Warning:</strong> Modifying this phone number will remove verification.');
        }
        else {
          $element['value']['#disabled'] = TRUE;
          $expiration_date = $phone_verification->getCreatedTime() + $lifetime;

          if ($current_time < $expiration_date) {
            $t_args['@time'] = $date_formatter->formatTimeDiffUntil($expiration_date, [
              'granularity' => 2,
            ]);
            $element['value']['#description'] = $this->t('A verification code has been sent to this phone number. Go to the <a href="@url">verification form</a> and enter the code. The code will expire if it is not verified in @time.', $t_args);
          }
          else {
            // This message displays if we are waiting for cron to delete
            // expired verification codes.
            $element['value']['#description'] = $this->t('Verification code expired. Try again later.');
          }
        }
      }
      else {
        // This message will display if there is a field value, but the
        // verification expired.
        $t_args['@time'] = $date_formatter->formatInterval($lifetime, 2);
        $element['value']['#description'] = $this->t('Save this form to send a new verification code as an SMS message, you must enter the code into the <a href="@url">verification form</a> within @time.', $t_args);
      }
    }
    else {
      $t_args['@time'] = $date_formatter->formatInterval($lifetime, 2);
      $element['value']['#description'] = $this->t('Enter a phone number. A verification code will be sent as an SMS message, you must enter the code into the <a href="@url">verification form</a> within @time.', $t_args);
    }

    return $element;
  }

}
