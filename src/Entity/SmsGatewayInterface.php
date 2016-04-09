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
   * @return \Drupal\sms\Plugin\SmsGatewayPluginInterface
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

  /**
   * Get whether messages sent to this gateway should be sent immediately.
   *
   * @return boolean
   *   Whether messages sent to this gateway should be sent immediately.
   */
  public function getSkipQueue();

  /**
   * Set whether messages sent to this gateway should be sent immediately.
   *
   * @param boolean $skip_queue
   *   Whether messages sent to this gateway should be sent immediately.
   *
   * @return $this
   *   Return this gateway for chaining.
   */
  public function setSkipQueue($skip_queue);

  /**
   * Get how many seconds to hold messages after they are sent.
   *
   * Use -1 to never expire.
   *
   * @return int
   *   How long messages should persist in seconds.
   */
  public function getRetentionDuration();

  /**
   * Set how many seconds to hold messages after they are sent.
   *
   * @param int $retention_duration
   *   How many seconds to hold messages, or use -1 to never expire.
   *
   * @return $this
   *   Return this gateway for chaining.
   */
  public function setRetentionDuration($retention_duration);

}
