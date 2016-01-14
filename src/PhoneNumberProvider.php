<?php

/**
 * @file
 * Contains \Drupal\sms\PhoneNumberProvider.
 */

namespace Drupal\sms;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\Core\Entity\EntityFieldManagerInterface;
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
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new PhoneNumberProvider object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  function __construct(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
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

    /** @var \Drupal\sms\Provider\DefaultSmsProvider $sms_provider */
    $sms_provider = \Drupal::service('sms_provider');
    return $sms_provider->send($sms_message_new);
  }

  /**
   * {@inheritdoc}
   */
  function getPhoneVerification(EntityInterface $entity, $phone_number) {
    $entities = \Drupal::entityManager()->getStorage('sms_entity_phone_verification')
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
    $verification = \Drupal\sms\Entity\EntityPhoneVerification::create([
      'entity' => $entity,
      'phone' => $phone_number,
      'code' => mt_rand(1000, 9999),
      'status' => FALSE,
    ]);
    $verification->save();
  }

}
