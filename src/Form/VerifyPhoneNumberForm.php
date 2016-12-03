<?php

namespace Drupal\sms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Flood\FloodInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sms\Provider\PhoneNumberVerificationInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to accept a verification code.
 */
class VerifyPhoneNumberForm extends FormBase {

  /**
   * The flood control mechanism.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * The phone number verification service.
   *
   * @var \Drupal\sms\Provider\PhoneNumberVerificationInterface
   */
  protected $phoneNumberVerification;

  /**
   * Constructs a VerifyPhoneNumberForm object.
   *
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood control mechanism.
   * @param \Drupal\sms\Provider\PhoneNumberVerificationInterface $phone_number_verification
   *   The phone number verification service.
   */
  public function __construct(FloodInterface $flood, PhoneNumberVerificationInterface $phone_number_verification) {
    $this->flood = $flood;
    $this->phoneNumberVerification = $phone_number_verification;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flood'),
      $container->get('sms.phone_number.verification')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_verify_phone_number';
  }

  /**
   * {@inheritdoc}
   */
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
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $flood_window = $this->config('sms.settings')->get('flood.verify_window');
    $flood_limit = $this->config('sms.settings')->get('flood.verify_limit');

    if (!$this->flood->isAllowed('sms.verify_phone_number', $flood_limit, $flood_window)) {
      $form_state->setError($form, $this->t('There has been too many failed verification attempts. Try again later.'));
      return;
    }

    $current_time = $this->getRequest()->server->get('REQUEST_TIME');
    $code = $form_state->getValue('code');
    $phone_verification = $this->phoneNumberVerification
      ->getPhoneVerificationByCode($code);

    if ($phone_verification && !$phone_verification->getStatus()) {
      $entity = $phone_verification->getEntity();
      $phone_number_settings = $this->phoneNumberVerification
        ->getPhoneNumberSettingsForEntity($entity);
      $lifetime = $phone_number_settings->getVerificationCodeLifetime() ?: 0;

      if ($current_time > $phone_verification->getCreatedTime() + $lifetime) {
        $form_state->setError($form['code'], $this->t('Verification code is expired.'));
      }
    }
    else {
      $form_state->setError($form['code'], $this->t('Invalid verification code.'));
    }

    $this->flood
      ->register('sms.verify_phone_number', $flood_window);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $code = $form_state->getValue('code');
    $phone_verification = $this->phoneNumberVerification
      ->getPhoneVerificationByCode($code);
    $phone_verification
      ->setStatus(TRUE)
      ->setCode('')
      ->save();
    drupal_set_message($this->t('Phone number is now verified.'));
  }

}
