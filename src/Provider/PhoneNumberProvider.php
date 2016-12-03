<?php

namespace Drupal\sms\Provider;

use Drupal\Core\Entity\EntityInterface;
use Drupal\sms\Direction;
use Drupal\sms\Entity\SmsMessage as SmsMessageEntity;
use Drupal\sms\Exception\NoPhoneNumberException;
use Drupal\sms\Exception\PhoneNumberSettingsException;
use Drupal\sms\Message\SmsMessageInterface;

/**
 * Phone number provider.
 */
class PhoneNumberProvider implements PhoneNumberProviderInterface {

  /**
   * The SMS provider.
   *
   * @var \Drupal\sms\Provider\SmsProviderInterface
   */
  protected $smsProvider;

  /**
   * The phone number verification service.
   *
   * @var \Drupal\sms\Provider\PhoneNumberVerificationInterface
   */
  protected $phoneNumberVerification;

  /**
   * Constructs a new PhoneNumberProvider object.
   *
   * @param \Drupal\sms\Provider\SmsProviderInterface $sms_provider
   *   The SMS provider.
   * @param \Drupal\sms\Provider\PhoneNumberVerificationInterface $phone_number_verification
   *   The phone number verification service.
   */
  public function __construct(SmsProviderInterface $sms_provider, PhoneNumberVerificationInterface $phone_number_verification) {
    $this->smsProvider = $sms_provider;
    // Temporarily inject service until an event is created.
    // See: https://www.drupal.org/node/2797121
    $this->phoneNumberVerification = $phone_number_verification;
  }

  /**
   * {@inheritdoc}
   */
  public function getPhoneNumbers(EntityInterface $entity, $verified = TRUE) {
    $phone_number_settings = $this->phoneNumberVerification
      ->getPhoneNumberSettingsForEntity($entity);
    $field_name = $phone_number_settings->getFieldName('phone_number');

    if (!$field_name) {
      throw new PhoneNumberSettingsException(sprintf('Entity phone number config field mapping not set for bundle %s:%s', $entity->getEntityTypeId(), $entity->bundle()));
    }

    $phone_numbers = [];
    if (isset($entity->{$field_name})) {
      foreach ($entity->{$field_name} as $index => &$item) {
        $phone_numbers[$index] = $item->value;
      }
    }

    if (isset($verified)) {
      return array_filter($phone_numbers, function ($phone_number) use (&$entity, $verified) {
        $verification = $this->phoneNumberVerification
          ->getPhoneVerificationByEntity($entity, $phone_number);
        return $verification && ($verification->getStatus() == $verified);
      });
    }

    return $phone_numbers;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(EntityInterface $entity, SmsMessageInterface $sms_message) {
    if (!$phone_numbers = $this->getPhoneNumbers($entity)) {
      throw new NoPhoneNumberException('Attempted to send an SMS to entity without a phone number.');
    }

    $sms_message = SmsMessageEntity::convertFromSmsMessage($sms_message)
      ->addRecipient(reset($phone_numbers))
      ->setRecipientEntity($entity)
      ->setDirection(Direction::OUTGOING);

    $this->smsProvider
      ->queue($sms_message);
  }

}
