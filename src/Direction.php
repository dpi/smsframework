<?php

namespace Drupal\sms;

/**
 * Contains direction constants for SMS Framework
 */
final class Direction {

  /**
   * Whether the message is queued to be sent from the website.
   *
   * @var integer
   */
  const OUTGOING = 1;

  /**
   * Whether the message was received by the website.
   *
   * @var integer
   */
  const INCOMING = -1;

}
