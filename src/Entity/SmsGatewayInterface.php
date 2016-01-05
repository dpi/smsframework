<?php

/**
 * @file
 * Contains \Drupal\sms\Entity\SmsGatewayInterface.
 */

namespace Drupal\sms\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a SMS Gateway entity.
 */
interface SmsGatewayInterface extends ConfigEntityInterface {

  /**
   * Returns the plugin instance.
   *
   * @return \Drupal\sms\Gateway\SmsGatewayPluginInterface
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
