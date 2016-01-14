<?php


namespace Drupal\sms\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

interface PhoneNumberVerificationInterface extends ContentEntityInterface {

  public function getCreatedTime();
  public function getStatus();
  public function setStatus($status);

}