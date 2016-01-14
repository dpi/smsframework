<?php

/**
 * @file
 * Contains \Drupal\sms\Entity\PhoneNumberVerification.
 */

namespace Drupal\sms\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the phone number verification entity.
 *
 * @ContentEntityType(
 *   id = "sms_phone_number_verification",
 *   label = @Translation("Phone Number Verification"),
 *   base_table = "sms_phone_number_verification",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 * )
 */
class PhoneNumberVerification extends ContentEntityBase implements PhoneNumberVerificationInterface {

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', (bool) $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Phone verification ID'))
      ->setDescription(t('The phone verification ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['entity'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Entity'))
      ->setDescription(t('The entity for this verification code.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['phone'] = BaseFieldDefinition::create('telephone')
      ->setLabel(t('Phone'))
      ->setDescription(t('Phone number.'))
      ->setDefaultValue('')
      ->setCardinality(1)
      ->setDisplayOptions('form', [
        'type' => 'hidden',
      ]);

    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Verification code'))
      ->setDescription(t('The generated verification code.'))
      ->setRequired(TRUE)
      ->setDefaultValue('');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(t('The time that the verification code was created.'));

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('Verification status. 0=not verified, 1=verified.'))
      ->setDefaultValue(FALSE)
      ->setRequired(TRUE);

    return $fields;
  }

}
