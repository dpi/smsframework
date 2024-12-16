<?php

declare(strict_types=1);

namespace Drupal\sms\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\sms\Entity\PhoneNumberVerificationInterface as EntityPhoneNumberVerificationInterface;
use Drupal\sms\Provider\PhoneNumberVerificationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Destination plugin for SMS phone number verifications.
 *
 * @MigrateDestination(
 *   id = "entity:sms_phone_number_verification"
 * )
 */
class SmsVerification extends EntityContentBase implements ContainerFactoryPluginInterface {

  /**
   * The phone number verification service.
   */
  protected PhoneNumberVerificationInterface $phoneNumberVerificationService;

  /**
   * Builds a phone number verification entity destination.
   */
  final public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    EntityStorageInterface $storage,
    array $bundles,
    EntityFieldManagerInterface $entity_field_manager,
    FieldTypePluginManagerInterface $field_type_manager,
    PhoneNumberVerificationInterface $verification,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $storage, $bundles, $entity_field_manager, $field_type_manager);
    $this->phoneNumberVerificationService = $verification;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL): static {
    $entity_type = static::getEntityTypeId($plugin_id);
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration ?? throw new \LogicException('Missing migration'),
      $container->get('entity_type.manager')->getStorage($entity_type),
      \array_keys($container->get('entity_type.bundle.info')->getBundleInfo($entity_type)),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('sms.phone_number.verification'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    /** @var string[] $return */
    $return = parent::import($row, $old_destination_id_values);
    if ($return !== []) {
      // After successful import of the verification data, the phone number
      // should be updated on the corresponding user entity.
      /** @var \Drupal\sms\Entity\PhoneNumberVerification $verification */
      $verification = $this->storage->load(\reset($return));
      // @phpstan-ignore-next-line
      $this->setVerifiedValue($verification, (int) $row->getSourceProperty('delta'));
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier): void {
    /** @var \Drupal\sms\Entity\PhoneNumberVerification $verification */
    $verification = $this->storage->load(\reset($destination_identifier));
    $this->unsetVerifiedValue($verification);
    // Remove the verified user phone number.
    parent::rollback($destination_identifier);
  }

  /**
   * Sets the verified value for the user entity.
   *
   * @param \Drupal\sms\Entity\PhoneNumberVerificationInterface $verification
   *   The phone number verification for a given user entity.
   * @param int|null $delta
   *   The specific item of the phone number field to set.
   */
  protected function setVerifiedValue(EntityPhoneNumberVerificationInterface $verification, ?int $delta): void {
    $delta ??= 0;
    $user_entity = $verification->getEntity();
    if ($user_entity !== NULL) {
      $phone_number_settings = $this->phoneNumberVerificationService->getPhoneNumberSettingsForEntity($user_entity);
      $phone_field_name = $phone_number_settings->getFieldName('phone_number');
      // @phpstan-ignore-next-line
      $user_entity->{$phone_field_name}[$delta] = $verification->getPhoneNumber();
      $user_entity->save();
    }
  }

  /**
   * Unsets the verified value for the user entity.
   *
   * @param \Drupal\sms\Entity\PhoneNumberVerificationInterface $verification
   *   The phone number verification for a given user entity.
   */
  protected function unsetVerifiedValue(EntityPhoneNumberVerificationInterface $verification): void {
    $user_entity = $verification->getEntity();
    if ($user_entity instanceof FieldableEntityInterface) {
      $phone_number_settings = $this->phoneNumberVerificationService
        ->getPhoneNumberSettingsForEntity($user_entity);
      $phone_field_name = $phone_number_settings->getFieldName('phone_number');
      if ($phone_field_name !== NULL) {
        $user_entity->set($phone_field_name, '')->save();
      }
    }
  }

}
