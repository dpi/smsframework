<?php

/**
 * @file
 * Contains \Drupal\sms\Entity\SmsMessageInterface.
 */

namespace Drupal\sms\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\sms\Message\SmsMessageInterface as PlainSmsMessageInterface;
use Drupal\Core\Entity\EntityInterface;

interface SmsMessageInterface extends ContentEntityInterface, PlainSmsMessageInterface {

  /**
   * Whether the message is queued to be sent from the website.
   */
  const DIRECTION_OUTGOING = 1;

  /**
   * Whether the message was received by the website.
   */
  const DIRECTION_INCOMING = -1;

  /**
   * Get the gateway for this message.
   *
   * @return string
   *   A gateway plugin ID.
   */
  public function getGateway();

  /**
   * Set the gateway for this message.
   *
   * @param string $gateway
   *   A gateway plugin ID.
   *
   * @return $this
   *   Return SMS message for chaining.
   */
  public function setGateway($gateway);

  public function getSenderNumber();

  public function setSenderNumber($number);

  /**
   * Gets the entity who sent the SMS message.
   *
   * @return \Drupal\Core\Entity\EntityInterface|NULL
   *   The entity who sent the SMS message, or NULL if it is missing.
   */
  public function getSenderEntity();

  /**
   * Set the entity who sent the SMS message.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity who sent the SMS message.
   *
   * @return $this
   *   Return SMS message for chaining.
   */
  public function setSenderEntity(EntityInterface $entity);

  /**
   * Gets the entity who will receive the SMS message.
   *
   * @return \Drupal\Core\Entity\EntityInterface|NULL
   *   The entity who will receive the SMS message, or NULL if it is missing.
   */
  public function getRecipientEntity();

  /**
   * Set the entity who will receive the SMS message.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity who will receive the SMS message.
   *
   * @return $this
   *   Return SMS message for chaining.
   */
  public function setRecipientEntity(EntityInterface $entity);

}