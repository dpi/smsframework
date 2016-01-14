<?php


namespace Drupal\sms\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

interface PhoneNumberVerificationInterface extends ContentEntityInterface {

  public function getCreatedTime();

  /**
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getEntity();

  /**
   * @param string $code
   *
   * @return $this
   */
  public function setCode($code);
  public function getCode();

  /**
   * @param bool $status
   *
   * @return $this
   */
  public function setStatus($status);
  public function getStatus();

}