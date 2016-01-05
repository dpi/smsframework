<?php

/**
 * @file
 * Contains \Drupal\sms\SmsGatewayPluginInterface
 */
namespace Drupal\sms\Gateway;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\sms\Entity\SmsGatewayInterface;

/**
 * Manages SMS Gateways.
 */
interface SmsGatewayPluginManagerInterface extends PluginManagerInterface {

  /**
   * Get the default SMS gateway.
   *
   * @return \Drupal\sms\Entity\SmsGatewayInterface|FALSE
   *   A SmsGateway config entity, or FALSE if default gateway is not set or
   *   invalid.
   */
  public function getDefaultGateway();

  /**
   * Sets the default site SMS Gateway.
   *
   * @param \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway
   *   The new site default SMS Gateway.
   */
  public function setDefaultGateway(SmsGatewayInterface $sms_gateway);

}
