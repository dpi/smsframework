<?php


namespace Drupal\sms\Entity;

interface EntityPhoneVerificationInterface {

  public function getCreatedTime();

  public function getStatus();
  public function setStatus($status);

}