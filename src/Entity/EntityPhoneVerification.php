<?php

/**
 * @file
 * Contains \Drupal\sms\Entity\EntityPhoneVerification.
 */

namespace Drupal\sms\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the entity phone verification entity.
 *
 * @ContentEntityType(
 *   id = "sms_entity_phone_verification",
 *   label = @Translation("Entity Phone Verification"),
 *   base_table = "sms_entity_phone_verification",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 * )
 */
class EntityPhoneVerification extends ContentEntityBase implements EntityPhoneVerificationInterface {

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
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
