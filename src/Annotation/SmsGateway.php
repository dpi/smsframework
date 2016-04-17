<?php

/**
 * @file
 * Contains \Drupal\sms\Annotation\SmsGateway
 */

namespace Drupal\sms\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines SmsGateway Annotation object.
 *
 * @Annotation
 */
class SmsGateway extends Plugin {

  /**
   * The machine name of the sms gateway.
   *
   * @var string
   */
  protected $id;

  /**
   * Translated user-readable label.
   * @var string
   */
  protected $label;

  /**
   * Maximum number of recipients per outgoing message.
   *
   * Use -1 for no limit.
   *
   * @var int
   */
  protected $outgoing_message_max_recipients;

}
