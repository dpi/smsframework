<?php

/**
 * @file
 * Contains \Drupal\sms\Form\VerifyPhoneNumberForm.
 */

namespace Drupal\sms\Form;

use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sms\PhoneNumberProviderInterface;
use Drupal\Core\Form\FormStateInterface;

class VerifyPhoneNumberForm extends FormBase {

  /**
   * Phone number provider.
   *
   * @var \Drupal\sms\PhoneNumberProviderInterface
   */
  protected $phoneNumberProvider;

  /**
   * Constructs a VerifyPhoneNumberForm object.
   *
   * @param \Drupal\sms\PhoneNumberProviderInterface $phone_number_provider
   *   The phone number provider.
   */
  public function __construct(PhoneNumberProviderInterface $phone_number_provider) {
    $this->phoneNumberProvider = $phone_number_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sms.phone_number')
    );
  }

  public function getFormId() {
    return 'sms_verify_phone_number';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['code'] = [
      '#title' => $this->t('Verification code'),
      '#description' => $this->t('Enter the code you received from a SMS message.'),
      '#type' => 'textfield',
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Verify code'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $code = $form_state->getValue('code');
    $phone_verification = $this->phoneNumberProvider
      ->getPhoneVerificationCode($code);

    if ($phone_verification && !$phone_verification->getStatus()) {
      $expiration_seconds = 3600;
      if ((time() - ($phone_verification->getCreatedTime() + $expiration_seconds)) > 0) {
        $form_state->setError($form['code'], $this->t('Verification code is expired.'));
      }
    }
    else {
      $form_state->setError($form['code'], $this->t('Invalid verification code.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $code = $form_state->getValue('code');
    $phone_verification = $this->phoneNumberProvider
      ->getPhoneVerificationCode($code);
    $phone_verification
      ->setStatus(TRUE)
      ->setCode('')
      ->save();
    drupal_set_message($this->t('Phone number is now verified.'));
  }

}