<?php

/**
 * @file
 * Contains \Drupal\sms\Entity\SmsMessageInterface.
 */

namespace Drupal\sms\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\sms\Message\SmsMessageInterface as PlainSmsMessageInterface;

//interface SmsMessageInterface extends ContentEntityInterface, PlainSmsMessageInterface {
interface SmsMessageInterface extends ContentEntityInterface {

  /**
   * Whether the message is queued to be sent from the website.
   */
  const DIRECTION_OUTGOING = 1;

  /**
   * Whether the message was received by the website.
   */
  const DIRECTION_INCOMING = -1;

}