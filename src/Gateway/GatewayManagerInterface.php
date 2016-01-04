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

}
