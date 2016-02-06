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
use Drupal\sms\Exception\NoPhoneNumberException;
use Drupal\Core\Entity\EntityStorageException;

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
   * @param \Drupal\Core\Utility\Token $token
   *   The token replacement system.
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
  public function getPhoneNumbers(EntityInterface $entity, $verified = TRUE) {
    $phone_number_settings = $this->getPhoneNumberSettingsForEntity($entity);
    if (!$field_name = $phone_number_settings->get('fields.phone_number')) {
      throw new PhoneNumberSettingsException(sprintf('Entity phone number config field mapping not set for bundle %s:%s', $entity->getEntityTypeId(), $entity->bundle()));
    }

    $phone_numbers = [];
    if (isset($entity->{$field_name})) {
      foreach ($entity->{$field_name} as $index => &$item) {
        $phone_numbers[$index] = $item->value;
      }
    }

    if (isset($verified)) {
      return array_filter($phone_numbers, function($phone_number) use(&$entity, $verified) {
        $verification = $this->getPhoneVerificationByEntity($entity, $phone_number);
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

    if (!$config || !$config->get()) {
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
      $sms_message = new SmsMessage(
        '',
        [$phone_number],
        $message,
        [],
        0
      );
      $data['sms-message'] = $sms_message;
      $data['sms_verification_code'] = $phone_verification->getCode();
      $message = $this->token
        ->replace($message, $data);

      // @todo replace with setMesssage().
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

  /**
   * {@inheritdoc}
   */
  public function purgeExpiredVerifications() {
    $current_time = \Drupal::request()->server->get('REQUEST_TIME');

    $verification_ids = [];
    foreach ($this->configFactory->listAll('sms.phone.') as $config_id) {
      $config = $this->configFactory->get($config_id);
      $lifetime = $config->get('duration_verification_code_expire');
      if (!empty($lifetime)) {
        $verification_ids += $this->phoneNumberVerificationStorage->getQuery()
          ->condition('entity__target_type', $config->get('entity_type'))
          ->condition('bundle', $config->get('bundle'))
          ->condition('status', 0)
          ->condition('created', ($current_time - $lifetime), '<')
          ->execute();
      }
    }

    /** @var \Drupal\sms\Entity\PhoneNumberVerificationInterface $phone_number_verification */
    foreach ($this->phoneNumberVerificationStorage->loadMultiple($verification_ids) as $phone_number_verification) {
      if ($entity = $phone_number_verification->getEntity()) {
        try {
          $config = $this->getPhoneNumberSettingsForEntity($entity);
          $purge = $config->get('verification_phone_number_purge');
          $field_name = $config->get('fields.phone_number');
          if (!empty($purge) && isset($entity->{$field_name})) {
            $entity->{$field_name}->filter(function ($item) use ($phone_number_verification) {
              return $item->value != $phone_number_verification->getPhoneNumber();
            });
            $entity->save();
          }
        }
        catch (EntityStorageException $e) {
          // Failed to save entity.
        }
      }
      $this->phoneNumberVerificationStorage->delete([$phone_number_verification]);
    }
  }

}
