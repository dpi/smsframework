<?php

/**
 * @file
 * Contains \Drupal\sms\Provider\PhoneNumberProvider.
 */

namespace Drupal\sms\Provider;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\sms\Exception\PhoneNumberSettingsException;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessage;
use Drupal\Component\Utility\Random;

/**
 * Phone number provider.
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
   * Storage for Phone Number Verification entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $phoneNumberVerificationStorage;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new PhoneNumberProvider object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\sms\Provider\SmsProviderInterface $sms_provider
   *   The SMS provider.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, Token $token, SmsProviderInterface $sms_provider) {
    $this->smsProvider = $sms_provider;
    $this->phoneNumberVerificationStorage = $entity_type_manager
      ->getStorage('sms_phone_number_verification');
    $this->token = $token;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getPhoneNumbers(EntityInterface $entity) {
    $phone_number_settings = $this->getPhoneNumberSettingsForEntity($entity);
    if (!$field_name = $phone_number_settings->get('fields.phone_number')) {
      throw new PhoneNumberSettingsException(sprintf('Entity phone number config field mapping not set for bundle %s:%s', $entity->getEntityTypeId(), $entity->bundle()));
    }

    $numbers = [];
    if (isset($entity->{$field_name})) {
      foreach ($entity->{$field_name} as $index => &$item) {
        $numbers[$index] = $item->value;
      }
    }
    return $numbers;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(EntityInterface $entity, SmsMessageInterface $sms_message) {
    $phone_numbers = $this->getPhoneNumbers($entity);

    // @todo: remove this re-creation of SmsMessage when it adds setters.
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
  public function getPhoneNumberSettings($entity_type_id, $bundle) {
    return $this->configFactory->get('sms.phone.' . $entity_type_id . '.' . $bundle);
  }

  /**
   * {@inheritdoc}
   */
  public function getPhoneNumberSettingsForEntity(EntityInterface $entity) {
    $config = $this->getPhoneNumberSettings($entity->getEntityTypeId(), $entity->bundle());

    if (!$config->get()) {
      throw new PhoneNumberSettingsException(sprintf('Entity phone number config does not exist for bundle %s:%s', $entity->getEntityTypeId(), $entity->bundle()));
    }

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getPhoneVerificationByCode($code) {
    $entities = $this->phoneNumberVerificationStorage
      ->loadByProperties([
        'code' => $code,
      ]);
    return reset($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function getPhoneVerificationByEntity(EntityInterface $entity, $phone_number) {
    $entities = $this->phoneNumberVerificationStorage
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
  public function newPhoneVerification(EntityInterface $entity, $phone_number) {
    $config = $this->getPhoneNumberSettingsForEntity($entity);
    $message = $config->get('verification_message') ?: '';

    // @todo Replace with code generator.
    $random = new Random;
    $code = strtoupper($random->name(6));

    /** @var \Drupal\sms\Entity\PhoneNumberVerificationInterface $phone_verification */
    $phone_verification = $this->phoneNumberVerificationStorage->create();
    $phone_verification
      ->setCode($code)
      ->setStatus(FALSE)
      ->setPhoneNumber($phone_number)
      ->setEntity($entity)
      ->save();

    if ($phone_verification) {
      $data['sms_verification_code'] = $phone_verification->getCode();
      $message = $this->token
        ->replace($message, $data);
      $sms_message = new SmsMessage(
        '',
        [$phone_number],
        $message,
        [],
        0
      );
      $sms_message->setIsAutomated(FALSE);

      $this->smsProvider
        ->send($sms_message, []);
    }

    return $phone_verification;
  }

}
