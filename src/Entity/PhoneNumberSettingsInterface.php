<?php

/**
 * @file
 * Contains \Drupal\sms\Entity\PhoneNumberSettingsInterface.
 */

namespace Drupal\sms\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

interface PhoneNumberSettingsInterface extends ConfigEntityInterface {

  public function setPhoneNumberEntityTypeId($entity_type_id);
  public function getPhoneNumberEntityTypeId();
  public function setPhoneNumberBundle($bundle);
  public function getPhoneNumberBundle();

}