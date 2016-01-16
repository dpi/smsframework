<?php

/**
 * @file
 * Contains \Drupal\sms\Entity\PhoneNumberSettings.
 */

namespace Drupal\sms\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines storage for an SMS Gateway instance.
 *
 * @ConfigEntityType(
 *   id = "phone_number_settings",
 *   label = @Translation("Phone number settings"),
 *   config_prefix = "phone",
 *   admin_permission = "administer smsframework",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id"
 *   },
 *   handlers = {
 *     "list_builder" = "\Drupal\sms\Lists\PhoneNumberSettingsListBuilder",
 *     "form" = {
 *       "add" = "Drupal\sms\Form\PhoneNumberSettingsForm",
 *       "default" = "Drupal\sms\Form\PhoneNumberSettingsForm",
 *       "edit" = "Drupal\sms\Form\PhoneNumberSettingsForm",
 *       "delete" = "Drupal\sms\Form\PhoneNumberSettingsDeleteForm",
 *     }
 *   },
 *   links = {
 *     "canonical" = "/admin/config/smsframework/phone_number/{phone_number_settings}",
 *     "edit-form" = "/admin/config/smsframework/phone_number/{phone_number_settings}",
 *     "delete-form" = "/admin/config/smsframework/phone_number/{phone_number_settings}/delete",
 *   },
 * )
 */
class PhoneNumberSettings extends ConfigEntityBase implements PhoneNumberSettingsInterface {

  /**
   * Phone number settings entity ID.
   *
   * ID is a concatenation of entity type ID and bundle
   * "{entity_type_id}.{bundle}" suitable as config ID "sms.phone.*.*".
   *
   * @var string
   */
  protected $id;

  /**
   * Entity type ID of phone number settings.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * Bundle of phone number settings.
   *
   * @var string
   */
  protected $bundle;

  /**
   * Message template to send for phone number verification.
   *
   * @var string
   */
  protected $verification_message = '';

  /**
   * Number of seconds before phone number verifications expire.
   *
   * @var int
   */
  protected $duration_verification_code_expire = 0;

  /**
   * Whether to remove phone number field values when phone number
   * verifications expire.
   *
   * @var bool
   */
  protected $verification_phone_number_purge = TRUE;

  /**
   * Field name mapping.
   *
   * Keys are sms.phone.*.*.fields.$key, values are field names.
   *
   * @var string[string]
   */
  protected $fields = [];

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->entity_type . '.' . $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getPhoneNumberEntityTypeId() {
    return $this->entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function setPhoneNumberEntityTypeId($entity_type_id) {
    $this->entity_type = $entity_type_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPhoneNumberBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function setPhoneNumberBundle($bundle) {
    $this->bundle = $bundle;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVerificationMessage() {
    return $this->verification_message;
  }

  /**
   * {@inheritdoc}
   */
  public function setVerificationMessage($message) {
    $this->verification_message = $message;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVerificationLifetime() {
    return $this->duration_verification_code_expire;
  }

  /**
   * {@inheritdoc}
   */
  public function setVerificationLifetime($lifetime) {
    $this->duration_verification_code_expire = $lifetime;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isVerificationPhoneNumberPurge() {
    return $this->verification_phone_number_purge;
  }

  /**
   * {@inheritdoc}
   */
  public function setVerificationPhoneNumberPurge($purge) {
    $this->verification_phone_number_purge = $purge;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName($map) {
    return isset($this->fields[$map]) ? $this->fields[$map] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldName($map, $field_name) {
    $this->fields[$map] = $field_name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Delete associated phone number verifications.
    // Does not remove phone number field values.
    $verification_storage = \Drupal::entityTypeManager()
      ->getStorage('sms_phone_number_verification');

    $verification_ids = [];
    /** @var static $phone_number_settings */
    foreach ($entities as $phone_number_settings) {
      $verification_ids += $verification_storage->getQuery()
        ->condition('entity__target_type', $phone_number_settings->getPhoneNumberEntityTypeId())
        ->condition('bundle', $phone_number_settings->getPhoneNumberBundle())
        ->execute();
    }

    $verification_storage->delete($verification_storage->loadMultiple($verification_ids));
  }

}
