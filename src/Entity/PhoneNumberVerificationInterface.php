<?php


namespace Drupal\sms\Entity;

interface PhoneNumberVerificationInterface {

  public function getCreatedTime();
  public function getStatus();
  public function setStatus($status);

}