<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumberVerification\Form;

use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\sms\PhoneNumberVerification\CodeGenerator\VerificationCode;
use Drupal\sms\PhoneNumberVerification\SmsPhoneNumberVerificationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @phpstan-import-type SmsVerificationParameter from \Drupal\sms\SmsCompilerPass
 */
final class Verification extends FormBase {

  private const FLOOD = 'sms.verify_phone_number';

  private const FLOOD_WINDOW = 21600;

  private const FLOOD_LIMIT = 5;

  final public function __construct(
    private FloodInterface $flood,
    private SmsPhoneNumberVerificationInterface $phoneNumberVerification,
    private Url $successUrl,
    MessengerInterface $messenger,
  ) {
    $this->setMessenger($messenger);
  }

  public static function create(ContainerInterface $container): static {
    /** @var SmsVerificationParameter $verificationParam */
    $verificationParam = $container->getParameter('sms.verification');
    return new static(
      $container->get(FloodInterface::class),
      $container->get(SmsPhoneNumberVerificationInterface::class),
      Url::fromUserInput($verificationParam['route']['success']),
      $container->get(MessengerInterface::class),
    );
  }

  public function getFormId(): string {
    return 'sms_verification';
  }

  /**
   * @phpstan-param array<mixed> $form
   * @phpstan-return array<mixed>
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['code'] = [
      '#title' => $this->t('Verification code'),
      '#description' => $this->t('Enter the code you received from a SMS message.'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#attributes' => [
        // https://developer.chrome.com/docs/identity/web-apis/web-otp
        'autocomplete' => 'one-time-code',
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Verify code'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * @phpstan-param array<mixed> $form
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if (FALSE === $this->flood->isAllowed(self::FLOOD, static::FLOOD_LIMIT, static::FLOOD_WINDOW)) {
      $form_state->setError($form, $this->t('There has been too many failed verification attempts. Try again later.'));
      return;
    }

    /** @var non-empty-string $code */
    $code = $form_state->getValue('code');
    if (FALSE === $this->phoneNumberVerification->claimableVerificationByCodeExists(new VerificationCode($code))) {
      $form_state->setError($form['code'], $this->t('Invalid verification code.'));
    }

    $this->flood->register(self::FLOOD, static::FLOOD_WINDOW);
  }

  /**
   * @phpstan-param array<mixed> $form
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var non-empty-string $code */
    $code = $form_state->getValue('code');

    $this->phoneNumberVerification->verifyByCode(
      new VerificationCode($code),
    );

    $this->messenger()->addMessage($this->t('Phone number is now verified.'));
    $form_state->setRedirectUrl($this->successUrl);
  }

}
