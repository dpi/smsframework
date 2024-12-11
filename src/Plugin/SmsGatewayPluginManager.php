<?php

declare(strict_types=1);

namespace Drupal\sms\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\sms\Annotation;
use Drupal\sms\Attribute;

/**
 * Manages SMS gateways implemented using AnnotatedClassDiscovery.
 */
final class SmsGatewayPluginManager extends DefaultPluginManager implements SmsGatewayPluginManagerInterface {

  /**
   * Creates a new SmsGatewayPluginManager instance.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cacheBackend,
    ModuleHandlerInterface $moduleHandler,
  ) {
    parent::__construct(
      'Plugin/SmsGateway',
      $namespaces,
      $moduleHandler,
      SmsGatewayPluginInterface::class,
      Attribute\SmsGateway::class,
      Annotation\SmsGateway::class,
    );
    $this->setCacheBackend($cacheBackend, 'sms_gateways');
    $this->alterInfo('sms_gateway_info');
  }

}
