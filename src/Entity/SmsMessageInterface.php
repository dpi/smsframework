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
   * Get direction of the message.
   *
   * Direction is immutable so there is no setter. Set direction on creation.
   *
   * @return int
   *   See \Drupal\sms\Entity\SmsMessageInterface::DIRECTION_* constants for
   *   potential values.
   */
  public function getDirection();

  /**
   * Get the gateway for this message.
   *
   * @return \Drupal\sms\Entity\SmsGatewayInterface
   *   A gateway plugin instance.
   */
  public function getGateway();

  /**
   * Set the gateway for this message.
   *
   * @param \Drupal\sms\Entity\SmsGatewayInterface $gateway
   *   A gateway plugin instance.
   *
   * @return $this
   *   Return SMS message for chaining.
   */
  public function setGateway(SmsGatewayInterface $gateway);

  /**
   * Get phone number of the sender.
   *
   * @return string
   *   The phone number of the sender.
   */
  public function getSenderNumber();

  /**
   * Set the phone number of the sender.
   *
   * @param string $number
   *   The phone number of the sender.
   *
   * @return $this
   *   Return SMS message for chaining.
   */
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

  /**
   * Get whether the SMS message is in the queue to be processed.
   *
   * @return boolean
   *   Whether the SMS message is in the queue to be processed.
   */
  public function isQueued();

  /**
   * Get whether the SMS message is in the queue to be processed.
   *
   * @param bool $is_queued
   *   Whether the SMS message is in the queue to be processed.
   *
   * @return $this
   *   Return SMS message for chaining.
   */
  public function setQueued($is_queued);

  /**
   * Get the creation timestamp of the SMS message.
   *
   * @return int
   *   Creation timestamp of the SMS message.
   */
  public function getCreatedTime();

  /**
   * Get the time to send the SMS message.
   *
   * @return int
   *   The timestamp after which the SMS message should be sent.
   */
  public function getSendTime();

  /**
   * Set the time to send the SMS message.
   *
   * @param int $send_time
   *   The timestamp after which the SMS message should be sent.
   *
   * @return $this
   *   Return SMS message for chaining.
   */
  public function setSendTime($send_time);

  /**
   * The time the SMS message was processed.
   *
   * This value does not indicate whether the message was sent, only that the
   * gateway accepted the request.
   *
   * @return int|NULL
   *   The timestamp when SMS message was processed, or NULL if not processed.
   */
  public function getProcessedTime();

  /**
   * Set the time the SMS message was processed.
   *
   * @param int $processed
   *   The timestamp when SMS message was processed.
   *
   * @return $this
   *   Return SMS message for chaining.
   */
  public function setProcessedTime($processed);

}
