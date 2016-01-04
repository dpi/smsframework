<?php

/**
 * @file
 * Contains \Drupal\sms\Gateway\GatewayManager
 */

namespace Drupal\sms\Gateway;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\SmsGatewayInterface;

/**
 * Manages SMS gateways implemented using AnnotatedClassDiscovery
 */
class GatewayManager extends DefaultPluginManager implements GatewayManagerInterface {

  /**
   * Creates a new GatewayManager instance.
   *
   * @param \Traversable $namespaces
   *   The namespaces to search for the gateway plugins.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler for calling module hooks.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Gateway', $namespaces, $module_handler, 'Drupal\sms\Gateway\GatewayInterface', 'Drupal\sms\Annotation\SmsGateway');
    $this->setCacheBackend($cache_backend, 'sms_gateways');
    $this->alterInfo('sms_gateway_info');
  }

}
