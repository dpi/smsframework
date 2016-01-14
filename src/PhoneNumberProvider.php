<?php

/**
 * @file
 * Contains \Drupal\sms\PhoneNumberProvider.
 */

namespace Drupal\sms;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sms\Provider\SmsProviderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\sms\Exception\PhoneNumberConfiguration;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessage;

/**
 * Entity phone number provider.
 */
class PhoneNumberProvider implements PhoneNumberProviderInterface {

  use ContainerAwareTrait;

  /**
   * The SMS provider.
   *
   * @var \Drupal\sms\Provider\SmsProviderInterface
   */
  protected $smsProvider;

  /**
   * Storage for Phone Verification entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $phoneVerificationStorage;

  /**
   * Constructs a new PhoneNumberProvider object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\sms\Provider\SmsProviderInterface $sms_provider
   *   The SMS provider.
   */
  function __construct(EntityTypeManagerInterface $entity_type_manager, SmsProviderInterface $sms_provider) {
    $this->smsProvider = $sms_provider;
    $this->phoneVerificationStorage = $entity_type_manager
      ->getStorage('sms_entity_phone_verification');
  }

  /**
   * {@inheritdoc}
   */
  function getPhoneNumbers(EntityInterface $entity) {
    $config = \Drupal::config('sms.phone.' . $entity->getEntityTypeId() . '.' . $entity->bundle());
    if (!$config->get()) {
      throw new PhoneNumberConfiguration(sprintf('Entity phone number config does not exist for bundle %s:%s', $entity->getEntityTypeId(), $entity->bundle()));
    }

    if (!$field_name = $config->get('fields.phone_number')) {
      throw new PhoneNumberConfiguration(sprintf('Entity phone number config field mapping not set for bundle %s:%s', $entity->getEntityTypeId(), $entity->bundle()));
    }

    $numbers = [];
    foreach ($entity->{$field_name} as $value) {
      $numbers[] = $value->value;
    }
    return $numbers;
  }

  /**
   * {@inheritdoc}
   */
  function sendMessage(EntityInterface $entity, SmsMessageInterface $sms_message) {
    $phone_numbers = $this->getPhoneNumbers($entity);

    $sms_message_new = new SmsMessage(
      $sms_message->getSender(),
      // @todo: Improve multiple number handling:
      [reset($phone_numbers)],
      $sms_message->getMessage(),
      $sms_message->getOptions(),
      0 // @todo: Remove UID.
    );

    $this->smsProvider
      // @todo: Remove $options.
      ->send($sms_message_new, []);
  }

  /**
   * {@inheritdoc}
   */
  function getPhoneVerificationCode($code) {
    $entities = $this->phoneVerificationStorage
      ->loadByProperties([
        'code' => $code,
      ]);
    return reset($entities);
  }

  /**
   * {@inheritdoc}
   */
  function getPhoneVerification(EntityInterface $entity, $phone_number) {
    $entities = $this->phoneVerificationStorage
      ->loadByProperties([
        'entity__target_id' => $entity->id(),
        'entity__target_type' => $entity->getEntityTypeId(),
        'phone' => $phone_number,
      ]);
    return reset($entities);
  }

  /**
   * {@inheritdoc}
   */
  function newPhoneVerification(EntityInterface $entity, $phone_number) {
    $verification = $this->phoneVerificationStorage->create([
      'entity' => $entity,
      'phone' => $phone_number,
      'code' => mt_rand(1000, 9999),
      'status' => FALSE,
    ]);
    $verification->save();
  }

}
