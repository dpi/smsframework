<?php

/**
 * @file
 * Contains \Drupal\sms\Form\VerifyPhoneNumberForm.
 */

namespace Drupal\sms\Form;

use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sms\Provider\PhoneNumberProviderInterface;
use Drupal\Core\Form\FormStateInterface;

class VerifyPhoneNumberForm extends FormBase {

  /**
   * Phone number provider.
   *
   * @var \Drupal\sms\Provider\PhoneNumberProviderInterface
   */
  protected $phoneNumberProvider;

  /**
   * Constructs a VerifyPhoneNumberForm object.
   *
   * @param \Drupal\sms\Provider\PhoneNumberProviderInterface $phone_number_provider
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
    $current_time = $this->getRequest()->server->get('REQUEST_TIME');
    $code = $form_state->getValue('code');
    $phone_verification = $this->phoneNumberProvider
      ->getPhoneVerificationByCode($code);

    if ($phone_verification && !$phone_verification->getStatus()) {
      $entity = $phone_verification->getEntity();
      $config = $this->phoneNumberProvider
        ->getPhoneNumberSettingsForEntity($entity);
      $lifetime = $config->get('duration_verification_code_expire') ?: 0;

      if ($current_time > $phone_verification->getCreatedTime() + $lifetime) {
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
      ->getPhoneVerificationByCode($code);
    $phone_verification
      ->setStatus(TRUE)
      ->setCode('')
      ->save();
    drupal_set_message($this->t('Phone number is now verified.'));
  }

}