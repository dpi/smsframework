<?php

/**
 * @file
 * Contains \Drupal\sms\Entity\PhoneNumberBundle.
 */

namespace Drupal\sms\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Defines storage for an SMS Gateway instance.
 *
 * @ConfigEntityType(
 *   id = "bundle_phone_number",
 *   label = @Translation("Phone number bundle configuration"),
 *   config_prefix = "phone",
 *   admin_permission = "administer smsframework",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id"
 *   },
 *   handlers = {
 *     "list_builder" = "\Drupal\sms\Lists\PhoneNumberListBuilder",
 *     "form" = {
 *       "add" = "Drupal\sms\Form\PhoneNumberForm",
 *       "default" = "Drupal\sms\Form\PhoneNumberForm",
 *       "edit" = "Drupal\sms\Form\PhoneNumberForm",
 *       "delete" = "Drupal\sms\Form\PhoneNumberDeleteForm",
 *     }
 *   },
 *   links = {
 *     "canonical" = "/admin/config/smsframework/phone/{bundle_phone_number}",
 *     "edit-form" = "/admin/config/smsframework/phone/{bundle_phone_number}",
 *     "delete-form" = "/admin/config/smsframework/phone/{bundle_phone_number}/delete",
 *   },
 * )
 */
class PhoneNumberBundle extends ConfigEntityBase implements PhoneNumberBundleInterface {

  protected $id;
  var $entity_type;
  var $bundle;
  var $verification_message;
  var $duration_verification_code_expire;
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
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

//    $storage = FieldStorageConfig::create([
//      'field_name' => $field_name,
//      'entity_type' => $this->entity_type,
//    ]);
    //$storage->save();
  }


}
