<?php

/**
 * @file
 * Contains \Drupal\sms\GatewayInterface
 */
namespace Drupal\sms\Gateway;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\sms\SmsGatewayInterface;

/**
 * Manages SMS Gateways.
 */
interface GatewayManagerInterface extends PluginManagerInterface {

  /**
   * Get the default SMS gateway.
   *
   * @return \Drupal\sms\SmsGatewayInterface|FALSE
   *   A SmsGateway config entity, or FALSE if default gateway is not set or
   *   invalid.
   */
  public function getDefaultGateway();

  /**
   * Sets the default site SMS Gateway.
   *
   * @param \Drupal\sms\SmsGatewayInterface $sms_gateway
   *   The new site default SMS Gateway.
   */
  public function setDefaultGateway(SmsGatewayInterface $sms_gateway);

}
