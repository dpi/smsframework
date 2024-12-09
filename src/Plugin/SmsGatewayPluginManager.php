<?php

declare(strict_types=1);

namespace Drupal\sms\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\sms\Annotation\SmsGateway;

/**
 * Manages SMS gateways implemented using AnnotatedClassDiscovery.
 */
class SmsGatewayPluginManager extends DefaultPluginManager implements SmsGatewayPluginManagerInterface {

  /**
   * Creates a new SmsGatewayPluginManager instance.
   *
   * @param \Traversable $namespaces
   *   The namespaces to search for the gateway plugins.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler for calling module hooks.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cacheBackend,
    ModuleHandlerInterface $module_handler,
  ) {
    parent::__construct('Plugin/SmsGateway', $namespaces, $module_handler, SmsGatewayPluginInterface::class, SmsGateway::class);
    $this->setCacheBackend($cacheBackend, 'sms_gateways');
    $this->alterInfo('sms_gateway_info');
  }

}
