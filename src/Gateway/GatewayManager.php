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
   * Configuration factory for this gateway manager.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * List of gateways managed by this gateway manager.
   *
   * @var \Drupal\sms\Gateway\GatewayInterface[]
   */
  protected $gateways;

  /**
   * List of human-readable names for the gateways.
   *
   * @var string[]
   */
  protected $names;

  /**
   * The default gateway.
   *
   * @var string
   */
  protected $defaultGateway;

  /**
   * Create new GatewayManager instance.
   *
   * @param \Traversable $namespaces
   *   The namespaces to search for the gateway plugins.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Handles the instantiation of gateways based on stored configuration.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   Module handler for calling module hooks.
   */
  public function __construct(\Traversable $namespaces, ConfigFactory $config_factory, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Gateway', $namespaces, $module_handler, 'Drupal\sms\Gateway\GatewayInterface', 'Drupal\sms\Annotation\SmsGateway');
    $this->setCacheBackend($cache_backend, 'sms_gateways');
    $this->alterInfo('sms_gateway_info');
    $this->configFactory = $config_factory;
    $this->defaultGateway = $this->configFactory->get('sms.settings')->get('default_gateway');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultGateway() {
    $gateway_id = $this->configFactory
      ->get('sms.settings')
      ->get('default_gateway');
    return SmsGateway::load($gateway_id);
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultGateway(SmsGatewayInterface $sms_gateway) {
    $this->configFactory
      ->getEditable('sms.settings')
      ->set('default_gateway', $sms_gateway->id())
      ->save();
  }

}
