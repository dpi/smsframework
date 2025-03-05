<?php

declare(strict_types=1);

namespace Drupal\sms\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\SynchronizableInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\sms\EntityMapping;
use Drupal\sms\PhoneNumber\PhoneNumber;
use Drupal\sms\PhoneNumber\PhoneNumberInterface;
use Drupal\sms\PhoneNumber\QueryOptions;
use Drupal\sms\PhoneNumber\SmsPhoneNumberInterface;
use Drupal\sms\PhoneNumberVerification\Enum\VerificationRequirement;
use Drupal\sms\PhoneNumberVerification\Event\EntityPhoneNumberVerification;
use Drupal\sms\PhoneNumberVerification\SmsPhoneNumberVerificationInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final class SmsVerificationHooks {

  /**
   * @internal
   */
  public function __construct(
    private readonly EntityMapping $entityMapping,
    private readonly SmsPhoneNumberInterface $smsPhoneNumber,
    private readonly SmsPhoneNumberVerificationInterface $phoneNumberVerification,
    private readonly EventDispatcherInterface $eventDispatcher,
  ) {
  }

  /**
   * Implements hook_entity_insert().
   */
  #[Hook('entity_insert')]
  public function entityInsert(EntityInterface $entity): void {
    $this->entityUpsert($entity);
  }

  /**
   * Implements hook_entity_update().
   */
  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity): void {
    $this->entityUpsert($entity);
  }

  private function entityUpsert(EntityInterface $entity): void {
    // Do not update verifications when _syncing_, e.g. migrations, etc.
    if (!$entity instanceof SynchronizableInterface || $entity->isSyncing() === FALSE) {
      $map = $this->entityMapping->getMappingFor($entity);
      if ($map?->verifications === TRUE) {
        $this->updatePhoneVerificationByEntity($entity);
      }
    }
  }

  /**
   * Implements hook_entity_delete().
   */
  #[Hook('entity_delete')]
  public function entityDelete(EntityInterface $entity): void {
    $map = $this->entityMapping->getMappingFor($entity);
    if ($map !== NULL) {
      $this->phoneNumberVerification->deletePhoneVerifications($entity);
    }
  }

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    $this->phoneNumberVerification->purgeExpiredVerifications();
  }

  /**
   * @phpstan-param T $entity
   * @template T of \Drupal\Core\Entity\EntityInterface
   */
  private function updatePhoneVerificationByEntity(EntityInterface $entity): void {
    $phoneNumbers = $this->smsPhoneNumber->getPhoneNumbers($entity, new QueryOptions(verified: VerificationRequirement::Any));

    foreach ($phoneNumbers as $phoneNumber) {
      if (NULL === $this->phoneNumberVerification->getPhoneNumberVerification($entity, $phoneNumber)) {
        $this->eventDispatcher->dispatch($event = EntityPhoneNumberVerification::create($entity, $phoneNumber));
        if ($event->isSendingVerification()) {
          $this->phoneNumberVerification->newPhoneVerification($entity, $phoneNumber);
        }
      }
    }

    // Delete verifications for phone numbers the entity no longer has.
    /** @var T|null $originalEntity */
    $originalEntity = $entity->original ?? NULL;
    if ($originalEntity !== NULL) {
      $phoneNumbersOriginal = $this->smsPhoneNumber->getPhoneNumbers($originalEntity, new QueryOptions(verified: VerificationRequirement::Any));
      $currentNumbersRaw = \array_map(static fn (PhoneNumberInterface $phoneNumber) => $phoneNumber->getPhoneNumber(), \iterator_to_array($phoneNumbers));
      $originalNumbersRaw = \array_map(static fn (PhoneNumberInterface $phoneNumber) => $phoneNumber->getPhoneNumber(), \iterator_to_array($phoneNumbersOriginal));

      foreach ($originalNumbersRaw as $originalNumberRaw) {
        if (!\in_array($originalNumberRaw, $currentNumbersRaw, TRUE)) {
          $verification = $this->phoneNumberVerification->getPhoneNumberVerification($entity, PhoneNumber::create($originalNumberRaw));
          if ($verification !== NULL) {
            $verification->delete();
          }
        }
      }
    }
  }

}
