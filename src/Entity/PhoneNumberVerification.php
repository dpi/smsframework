<?php

declare(strict_types=1);

namespace Drupal\sms\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sms\PhoneNumber\PhoneNumber;
use Drupal\sms\PhoneNumber\PhoneNumberInterface;
use Drupal\sms\PhoneNumberVerification\CodeGenerator\VerificationCode;
use Drupal\sms\PhoneNumberVerification\CodeGenerator\VerificationCodeInterface;
use Drupal\sms\PhoneNumberVerification\Enum\Verified;
use Drupal\sms\PhoneNumberVerification\Object\ObjectWithPhoneNumberAdapter;
use Drupal\sms\PhoneNumberVerification\Object\ObjectWithPhoneNumberInterface;

/**
 * Defines the phone number verification entity.
 *
 * @internal
 *   The entity is an internal concern. Use the phone number verification
 *   service (SmsPhoneNumberVerificationInterface) directly instead.
 *
 * @see \Drupal\sms\PhoneNumberVerification\SmsPhoneNumberVerificationInterface
 *
 * This class is part of the PhoneNumberVerification component.
 */
#[ContentEntityType(
  id: self::ENTITY_TYPE_ID,
  label: new TranslatableMarkup('Phone Number Verification'),
  label_collection: new TranslatableMarkup('Phone Number Verifications'),
  label_singular: new TranslatableMarkup('phone number verification'),
  label_plural: new TranslatableMarkup('phone number verifications'),
  entity_keys: [
    'id' => 'id',
  ],
  base_table: 'sms_phone_number_verification',
  internal: TRUE,
  label_count: [
    'singular' => '@count phone number verification',
    'plural' => '@count phone number verifications',
  ],
)]
final class PhoneNumberVerification extends ContentEntityBase implements PhoneNumberVerificationInterface {

  public const ENTITY_TYPE_ID = 'sms_phone_number_verification';

  public function getCreatedDate(): \DateTimeImmutable {
    // @phpstan-ignore-next-line
    return new \DateTimeImmutable('@' . (int) $this->get('created')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedDate(\DateTimeInterface $created) {
    return $this->set('created', $created->getTimestamp());
  }

  public function getFor(): ObjectWithPhoneNumberInterface|EntityInterface|null {
    $targetId = (string) $this->entity->target_id;
    // @phpstan-ignore-next-line
    $targetType = (string) $this->entity->target_type;
    $for = ObjectWithPhoneNumberAdapter::fromRaw($targetType, $targetId);
    return \Drupal::entityTypeManager()->hasDefinition($for->getVerifiedType())
      ? \Drupal::entityTypeManager()->getStorage($for->getVerifiedType())->load($targetId)
      : $for;
  }

  /**
   * {@inheritdoc}
   */
  public function setFor(ObjectWithPhoneNumberInterface|EntityInterface $for) {
    $for = ObjectWithPhoneNumberAdapter::from($for);
    $this->entity->target_id = $for->getVerifiedIdentifier();
    // @phpstan-ignore-next-line
    $this->entity->target_type = $for->getVerifiedType();
    return $this;
  }

  public function getPhoneNumber(): PhoneNumberInterface {
    /** @var non-empty-string $phoneNumber */
    // @phpstan-ignore-next-line
    $phoneNumber = (string) $this->get('phone')->value;
    return PhoneNumber::create($phoneNumber);
  }

  /**
   * {@inheritdoc}
   */
  public function setPhoneNumber(PhoneNumberInterface $phoneNumber) {
    return $this->set('phone', $phoneNumber->getPhoneNumber());
  }

  public function getVerificationCode(): ?VerificationCodeInterface {
    /** @var non-empty-string $code */
    // @phpstan-ignore-next-line
    $code = (string) $this->get('code')->value;
    return new VerificationCode($code);
  }

  /**
   * {@inheritdoc}
   */
  public function setVerificationCode(VerificationCodeInterface $verificationCode) {
    return $this->set('code', $verificationCode->getCode());
  }

  /**
   * {@inheritdoc}
   */
  public function clearVerificationCode() {
    return $this->set('code', NULL);
  }

  public function getStatus(): Verified {
    $status = (bool) $this->get('status')->value;
    return $status ? Verified::Verified : Verified::Unverified;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus(Verified $status) {
    $status = match ($status) {
      Verified::Verified => TRUE,
      Verified::Unverified => FALSE,
    };
    return $this->set('status', $status);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = [];

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(\t('Phone verification ID'))
      ->setDescription(\t('The phone verification ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['entity'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(\t('Entity'))
      ->setDescription(\t('The entity for this verification code.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    // Bundle field is required for statistics and bulk cleanup.
    $fields['bundle'] = BaseFieldDefinition::create('string')
      ->setLabel(\t('Bundle'))
      ->setDescription(\t('The bundle of the entity.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['phone'] = BaseFieldDefinition::create('telephone')
      ->setLabel(\t('Phone'))
      ->setDescription(\t('Phone number.'))
      ->setDefaultValue('')
      ->setCardinality(1)
      ->setDisplayOptions('form', [
        'type' => 'hidden',
      ]);

    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(\t('Verification code'))
      ->setDescription(\t('The generated verification code.'))
      ->setRequired(TRUE)
      ->setDefaultValue('');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(\t('Created on'))
      ->setDescription(\t('The time that the verification code was created.'));

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(\t('Status'))
      ->setDescription(\t('Verification status. 0=not verified, 1=verified.'))
      ->setDefaultValue(FALSE)
      ->setRequired(TRUE);

    return $fields;
  }

  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    // Update bundle field with bundle of entity.
    $entity = $this->getFor();
    if ($entity instanceof EntityInterface) {
      $this->set('bundle', $entity->bundle());
    }
  }

}
