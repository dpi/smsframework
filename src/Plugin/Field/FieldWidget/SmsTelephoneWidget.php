<?php

declare(strict_types=1);

namespace Drupal\sms\Plugin\Field\FieldWidget;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\sms\PhoneNumber\PhoneNumber;
use Drupal\sms\PhoneNumberVerification\Enum\Verified;
use Drupal\sms\PhoneNumberVerification\SmsPhoneNumberVerificationInterface;
use Drupal\telephone\Plugin\Field\FieldWidget\TelephoneDefaultWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Locks values awaiting verification.
 *
 * @FieldWidget(
 *   id = \Drupal\sms\Plugin\Field\FieldWidget\SmsTelephoneWidget::PLUGIN_ID,
 *   label = @Translation("SMS Framework Phone Verification"),
 *   field_types = {
 *     "telephone"
 *   }
 * )
 *
 * @phpstan-import-type SmsVerificationParameter from \Drupal\sms\SmsCompilerPass
 *
 * This class is part of the PhoneNumberVerification component.
 */
class SmsTelephoneWidget extends TelephoneDefaultWidget {

  public const PLUGIN_ID = 'sms_telephone';

  /**
   * @phpstan-param positive-int $unverifiedLifetime
   * @phpstan-param array<mixed> $settings
   * @phpstan-param array<mixed> $third_party_settings
   */
  final public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    private TimeInterface $time,
    private SmsPhoneNumberVerificationInterface $phoneNumberVerification,
    private DateFormatterInterface $dateFormatter,
    private readonly int $unverifiedLifetime,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<mixed> $configuration
   */
  final public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    /** @var SmsVerificationParameter $verification */
    $verification = $container->getParameter('sms.verification');
    return new static(
      $plugin_id,
      $plugin_definition,
      // @phpstan-ignore-next-line
      $configuration['field_definition'],
      // @phpstan-ignore-next-line
      $configuration['settings'],
      // @phpstan-ignore-next-line
      $configuration['third_party_settings'],
      $container->get(TimeInterface::class),
      $container->get(SmsPhoneNumberVerificationInterface::class),
      $container->get(DateFormatterInterface::class),
      $verification['unverified']['lifetime'],
    );
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $t_args = [];
    $t_args['@url'] = Url::fromRoute('sms.phone.verify')->toString();

    if (!isset($items[$delta]->value) || $items[$delta]->value === '') {
      $t_args['@time'] = $this->dateFormatter->formatInterval($this->unverifiedLifetime, 2);
      $element['value']['#description'] = $this->t('Enter a phone number. A verification code will be sent as an SMS message, you must enter the code into the <a href="@url">verification form</a> within @time.', $t_args);
      return $element;
    }

    $verification = $this->phoneNumberVerification
      ->getPhoneNumberVerification(
        $items->getEntity(),
        PhoneNumber::create($items[$delta]->value),
      );

    if ($verification === NULL) {
      // This message will display if there is a field value, but the
      // verification expired.
      $t_args['@time'] = $this->dateFormatter->formatInterval($this->unverifiedLifetime, 2);
      $element['value']['#description'] = $this->t('Save this form to send a new verification code as an SMS message, you must enter the code into the <a href="@url">verification form</a> within @time.', $t_args);

      return $element;
    }

    if ($verification->getStatus() === Verified::Verified) {
      $element['value']['#description'] = $this->t('This phone number is verified. <strong>Warning:</strong> Modifying this phone number will remove verification.');
    }
    else {
      $element['value']['#disabled'] = TRUE;
      $expiration_date = $verification->getCreatedDate()->modify(\sprintf('+ %d seconds', $this->unverifiedLifetime));

      if ($this->now() < $expiration_date) {
        $t_args['@time'] = $this->dateFormatter->formatTimeDiffUntil($expiration_date->getTimestamp(), [
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

    return $element;
  }

  private function now(): \DateTimeImmutable {
    return new \DateTimeImmutable('@' . $this->time->getRequestTime());
  }

}
