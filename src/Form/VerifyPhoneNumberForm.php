<?php

declare(strict_types=1);

namespace Drupal\sms\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\sms\Entity\PhoneNumberVerificationInterface as EntityPhoneNumberVerificationInterface;
use Drupal\sms\Provider\PhoneNumberVerificationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to accept a verification code.
 */
class VerifyPhoneNumberForm extends FormBase {

  private const FLOOD = 'sms.verify_phone_number';

  /**
   * Constructs a VerifyPhoneNumberForm object.
   */
  final public function __construct(
    protected FloodInterface $flood,
    protected PhoneNumberVerificationInterface $phoneNumberVerification,
    MessengerInterface $messenger,
    protected TimeInterface $time,
  ) {
    $this->setMessenger($messenger);
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('flood'),
      $container->get('sms.phone_number.verification'),
      $container->get('messenger'),
      $container->get('datetime.time'),
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
      '#value' => $this->t('Verify code'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    /** @var positive-int $flood_window */
    $flood_window = $this->config('sms.settings')->get('flood.verify_window');
    /** @var positive-int $flood_limit */
    $flood_limit = $this->config('sms.settings')->get('flood.verify_limit');

    if (FALSE === $this->flood->isAllowed(self::FLOOD, $flood_limit, $flood_window)) {
      $form_state->setError($form, $this->t('There has been too many failed verification attempts. Try again later.'));
      return;
    }

    $current_time = new \DateTimeImmutable('@' . $this->time->getRequestTime());
    /** @var string $code */
    $code = $form_state->getValue('code');
    $phone_verification = $this->phoneNumberVerification
      ->getPhoneVerificationByCode($code);

    if ($phone_verification instanceof EntityPhoneNumberVerificationInterface && FALSE === $phone_verification->getStatus()) {
      $entity = $phone_verification->getEntity();
      if ($entity === NULL) {
        $form_state->setError($form['code'], $this->t('Entity for this verification disappeared.'));
        return;
      }

      $phone_number_settings = $this->phoneNumberVerification->getPhoneNumberSettingsForEntity($entity);
      $lifetime = $phone_number_settings->getVerificationCodeLifetime();

      if ($current_time > $phone_verification->getCreatedDate()->modify('+' . $lifetime . ' seconds')) {
        $form_state->setError($form['code'], $this->t('Verification code is expired.'));
      }
    }
    else {
      $form_state->setError($form['code'], $this->t('Invalid verification code.'));
    }

    $this->flood
      ->register(self::FLOOD, $flood_window);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var string $code */
    $code = $form_state->getValue('code');

    // Guaranteed by validateForm:
    /** @var \Drupal\sms\Entity\PhoneNumberVerificationInterface $phone_verification */
    $phone_verification = $this->phoneNumberVerification->getPhoneVerificationByCode($code);
    $phone_verification
      ->setStatus(TRUE)
      ->setCode('')
      ->save();
    $this->messenger()->addMessage($this->t('Phone number is now verified.'));
  }

}
