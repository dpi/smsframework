<?php

declare(strict_types=1);

namespace Drupal\sms\Lists;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\sms\Provider\PhoneNumberVerificationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds a list of phone number settings.
 */
class PhoneNumberSettingsListBuilder extends ConfigEntityListBuilder {

  /**
   * Constructs a new PhoneNumberSettingsListBuilder.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    private readonly EntityStorageInterface $phoneNumberVerificationStorage,
    private readonly PhoneNumberVerificationInterface $phoneNumberVerificationProvider,
    private readonly TimeInterface $time,
  ) {
    parent::__construct($entity_type, $storage);
  }

  final public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager')->getStorage('sms_phone_number_verification'),
      $container->get('sms.phone_number.verification'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header = [];
    $header['entity_type'] = $this->t('Entity type');
    $header['bundle'] = $this->t('Bundle');
    $header['count_expired'] = $this->t('Expired');
    $header['count_verified'] = $this->t('Verified');
    $header['count_unverified'] = $this->t('Unverified');
    $header['count_total'] = $this->t('Total');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\sms\Entity\PhoneNumberSettingsInterface $entity */
    $entity_type_id = $entity->getPhoneNumberEntityTypeId();
    $bundle = $entity->getPhoneNumberBundle();
    $row['entity_type'] = $entity_type_id;
    $row['bundle'] = $bundle;

    $phone_number_settings = $this->phoneNumberVerificationProvider
      ->getPhoneNumberSettings($entity_type_id, $bundle);
    $lifetime = $phone_number_settings->getVerificationCodeLifetime() ?: 0;

    $row['count_expired'] = $this->buildPhoneNumberVerificationQuery($entity_type_id, $bundle)
      ->condition('status', 0)
      ->condition('created', ($this->time->getRequestTime() - $lifetime), '<')
      ->count()
      ->execute();

    $row['count_verified'] = $this->buildPhoneNumberVerificationQuery($entity_type_id, $bundle)
      ->condition('status', 1)
      ->count()
      ->execute();

    $row['count_unverified'] = $this->buildPhoneNumberVerificationQuery($entity_type_id, $bundle)
      ->condition('status', 0)
      ->count()
      ->execute();

    $row['count_total'] = $this->buildPhoneNumberVerificationQuery($entity_type_id, $bundle)
      ->count()
      ->execute();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $render = parent::render();
    $render['table']['#empty'] = \t('No phone number settings found.');
    return $render;
  }

  /**
   * Builds a phone number verification query.
   *
   * @param string $entity_type_id
   *   Entity type to query.
   * @param string $bundle
   *   Entity bundle to query.
   */
  protected function buildPhoneNumberVerificationQuery(string $entity_type_id, string $bundle): QueryInterface {
    return $this->phoneNumberVerificationStorage
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('entity__target_type', $entity_type_id)
      ->condition('bundle', $bundle);
  }

}
