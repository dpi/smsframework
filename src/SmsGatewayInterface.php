<?php

/**
 * @file
 * Contains \Drupal\sms\SmsGatewayInterface.
 */

namespace Drupal\sms;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a SMS Gateway entity.
 */
interface SmsGatewayInterface extends ConfigEntityInterface {

  /**
   * Returns the plugin instance.
   *
   * @return \Drupal\sms\Gateway\GatewayInterface
   *   The plugin instance for this SMS Gateway.
   */
  public function getPlugin();

  /**
   * Returns the plugin ID.
   *
   * @return string
   *   The plugin ID for this SMS Gateway.
   */
  public function getPluginId();

}
