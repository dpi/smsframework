<?php

declare(strict_types=1);

namespace Drupal\sms\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\sms\Entity\PhoneNumberVerification;
use Drupal\sms\Entity\PhoneNumberVerificationInterface;
use Drupal\sms\EntityMapping;
use Drupal\sms\LogReference;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles deletion of phone number verification.
 *
 * Unconditionally deletes a verification, without validating expiry, etc.
 *
 * @QueueWorker(
 *   id = \Drupal\sms\Plugin\QueueWorker\DeletePhoneVerification::PLUGIN_ID,
 *   title = @Translation("SMS message processor"),
 *   cron = {"time" = 60}
 * )
 *
 * @phpstan-import-type SmsVerificationParameter from \Drupal\sms\SmsCompilerPass
 *
 * This class is part of the PhoneNumberVerification component.
 */
final class DeletePhoneVerification extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public const PLUGIN_ID = 'sms.phone_verification_delete';

  /**
   * @phpstan-param array<mixed> $configuration
   */
  final public function __construct(
    array $configuration,
    $plugin_id,
    mixed $plugin_definition,
    private EntityTypeManagerInterface $entityTypeManager,
    private EntityMapping $entityMapping,
    private ?LoggerInterface $logger,
    private bool $purgeFieldData,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<mixed> $configuration
   */
  final public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    /** @var SmsVerificationParameter $verificationParam */
    $verificationParam = $container->getParameter('sms.verification');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get(EntityTypeManagerInterface::class),
      $container->get(EntityMapping::class),
      $container->get('logger.channel.sms.phone_number_verification', ContainerInterface::NULL_ON_INVALID_REFERENCE),
      $verificationParam['unverified']['purge_fields'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    /** @var array{id: positive-int} $data */
    $id = $data['id'];

    /** @var \Drupal\sms\Entity\PhoneNumberVerificationInterface|null $verification */
    $verification = $this->phoneNumberVerificationStorage()->load($id);
    if ($verification === NULL) {
      return;
    }

    $for = $verification->getFor();
    $phoneNumberRaw = $verification->getPhoneNumber()->getPhoneNumber();
    $this->logger?->info('Deleted verification associated with @entity @phone_number (#@id) as it expired', [
      '@entity' => (string) LogReference::create($for),
      '@id' => $verification->id(),
      '@phone_number' => (string) $verification->getPhoneNumber(),
    ]);

    $verification->delete();

    if ($for instanceof FieldableEntityInterface && $this->purgeFieldData) {
      $map = $this->entityMapping->getMappingFor($for);
      if ($map === NULL) {
        return;
      }

      $fieldList = $for->get($map->fieldName);
      $fieldList->filter(static function (FieldItemInterface $item) use ($phoneNumberRaw) {
        return $item->getString() !== $phoneNumberRaw;
      });
      $for->save();

      $this->logger?->info('Removed phone number @phone_number from @entity since its verification expired', [
        '@entity' => (string) LogReference::create($for),
        '@phone_number' => (string) $verification->getPhoneNumber(),
      ]);
    }
  }

  /**
   * Create a queue item from a message.
   *
   * @phpstan-return array{id: positive-int}
   */
  public static function createItemFrom(PhoneNumberVerificationInterface $verification): array {
    if ($verification->isNew()) {
      throw new \LogicException('Verification must be saved.');
    }

    /** @var positive-int $id */
    $id = (int) $verification->id();
    return ['id' => $id];
  }

  private function phoneNumberVerificationStorage(): EntityStorageInterface {
    return $this->entityTypeManager->getStorage(PhoneNumberVerification::ENTITY_TYPE_ID);
  }

}
