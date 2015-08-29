<?php

/**
 * @file
 * Contains \Drupal\sms\GatewayInterface
 */
namespace Drupal\sms\Gateway;

/**
 * Manages SMS Gateways.
 */
interface GatewayManagerInterface {

  /**
   * Get the list of all discoverable SMS gateways.
   *
   * @return \Drupal\sms\Gateway\GatewayInterface[]
   */
  public function getAvailableGateways();

  /**
   * Get the list of all enabled SMS gateways.
   *
   * @return \Drupal\sms\Gateway\GatewayInterface[]
   */
  public function getEnabledGateways();

  /**
   * Get the default SMS gateway.
   *
   * @return \Drupal\sms\Gateway\GatewayInterface
   */
  public function getDefaultGateway();

  /**
   * Get the gateway whose id is specified
   *
   * @param string $gateway_id
   *   The gateway id.
   * @return \Drupal\sms\Gateway\GatewayInterface
   */
  public function getGateway($gateway_id);

  /**
   * Sets the list of gateways that are enabled.
   *
   * @param array $gateway_ids
   *   An array of ids of the gateways to be enabled.
   */
  public function setEnabledGateways(array $gateway_ids);

  /**
   * Sets the default sms gateway for messaging.
   *
   * @param string $gateway_id
   *   The gateway id.
   */
  public function setDefaultGateway($gateway_id);

  /**
   * Gets the status of the specified gateway.
   *
   * @param string $gateway_id
   *   The id of the gateway to be checked.
   * @return bool
   *   TRUE if the gateway is enabled and FALSE otherwise.
   */
  public function isGatewayEnabled($gateway_id);

  /**
   * Creates a new gateway of a type from specified configuration.
   *
   * @param string $plugin_id
   *   The plugin ID of an existing gateway plugin.
   * @param array $configuration
   *   The configuration to be saved for the gateway. At a minimum must contain:
   *   - name: The machine name of the gateway
   *   - label: The human-readable name of the gateway
   *
   * @return \Drupal\sms\Gateway\GatewayInterface
   *   A newly created gateway instance.
   */
  public function addGateway($plugin_id, array $configuration);

  /**
   * Saves the configuration of a single gateway.
   *
   * @param \Drupal\sms\Gateway\GatewayInterface
   *   The gateway object whose configuration is to be saved.
   */
  public function saveGateway(GatewayInterface $gateway);

  /**
   * Saves the configuration of multiple gateways.
   *
   * @param \Drupal\sms\Gateway\GatewayInterface[]
   *   An array of gateway objects whose configuration is to be saved.
   */
  public function saveGateways(array $gateway);

  /**
   * Gets the list of gateway plugins discovered by this gateway manager.
   *
   * @return \Drupal\sms\Gateway\GatewayInterface[]
   */
  public function getGatewayPlugins();

}
