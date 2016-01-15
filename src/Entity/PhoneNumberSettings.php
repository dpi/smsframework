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

  protected $id;
  var $entity_type;
  var $bundle;
  var $verification_message;
  var $duration_verification_code_expire;
  var $verification_phone_number_purge;
  var $fields = [];

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->entity_type . '.' . $this->bundle;
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
  public function getPhoneNumberEntityTypeId() {
    return $this->entity_type;
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
  public function getPhoneNumberBundle() {
    return $this->bundle;
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
