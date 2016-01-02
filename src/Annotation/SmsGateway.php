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

}
