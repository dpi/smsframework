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
use Drupal\Core\StringTranslation\TranslationWrapper;

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
  public function getGatewayPlugins() {
    return $this->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function getGateway($gateway_id) {
    // Ensure gateway list has been built.
    $gateways = $this->getAvailableGateways();
    if (!isset($gateways[$gateway_id])) {
      return null;
    }
    else {
      return $gateways[$gateway_id];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultGateway() {
    if (!isset($this->defaultGateway)) {
      $this->defaultGateway = $this->configFactory->get('sms.settings')
        ->get('default_gateway');
    }
    return $this->getGateway($this->defaultGateway);
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultGateway($gateway_id) {
    // Cannot make a disabled gateway default.
    if ($this->getGateway($gateway_id)->isEnabled()) {
      $this->defaultGateway = $gateway_id;
      $this->configFactory->getEditable('sms.settings')
        ->set('default_gateway', $this->defaultGateway)
        ->save();
    }
    else {
      throw new \LogicException('A disabled gateway cannot be made the default.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableGateways() {
    if (!isset($this->gateways)) {
      $this->buildGateways();
    }
    return $this->gateways;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledGateways() {
    return array_filter($this->getAvailableGateways(), function(GatewayInterface $gateway) {
      $gateway->isEnabled();
    });
  }

  /**
   * {@inheritdoc}
   */
  public function setEnabledGateways(array $gateway_ids) {
    $gateways = [];
    foreach ($gateway_ids as $gateway_id) {
      $gateway = $this->getGateway($gateway_id);
      $gateway->setEnabled(TRUE);
      $gateways[] = $gateway;
    }
    $this->saveGateways($gateways);
  }

  /**
   * {@inheritdoc}
   */
  public function isGatewayEnabled($gateway_id) {
    return $this->getGateway($gateway_id)->isEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function saveGateway(GatewayInterface $gateway) {
    $this->saveGateways([$gateway]);
  }

  /**
   * {@inheritdoc}
   */
  public function saveGateways(array $gateways) {
    /** @var \Drupal\sms\Gateway\GatewayInterface $gateway */
    foreach ($gateways as $gateway) {
      $this->configFactory->getEditable('sms.gateway.' . $gateway->getName())
        ->setData($gateway->getConfiguration())
        ->save();
    }
    // @todo Implement more granular cache invalidations.
    // Clear static caches.
    $this->gateways = NULL;
    $this->names = NULL;
    // Clear dynamic caches.
    $this->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function addGateway($plugin_id, array $configuration) {
    // Initialize existing gateways first.
    if (!isset($this->gateways)) {
      $this->buildGateways();
    }
    $name = $configuration['name'];
    if (!isset($configuration['name'])) {
      throw new \InvalidArgumentException('A machine name is required to create an SMS gateway.');
    }
    $configuration['plugin_id'] = $plugin_id;
    $this->gateways[$name] = $gateway = $this->createInstance($plugin_id, $configuration);
    $this->names[$name] = $this->gateways[$name]->getLabel();
    $this->saveGateway($gateway);
  }

  /**
   * Builds the gateway plugin objects from the definitions.
   *
   * @return array
   *   \Drupal\sms\Gateway\GatewayInterface[]
   */
  protected function buildGateways() {
    // Get configured gateways.
    $names = $this->configFactory->listAll('sms.gateway.');
    foreach ($this->configFactory->loadMultiple($names) as $id => $instance_config) {
      $id = substr($id, 12);
      $settings = $instance_config->getRawData();
      // Set some sane defaults.
      $settings += ['name' => $id];

      // Note that DefaultFactory::createInstance will get the right definitions.
      if (!isset($settings['plugin_id'])) {
        throw new \InvalidArgumentException(sprintf('Gateway "%s" configured without a plugin id.', $id));
      }
      $this->gateways[$id] = $this->createInstance($settings['plugin_id'], $settings);
      $this->names[$id] = $this->gateways[$id]->getLabel();
    }
  }

  /**
   * {@inheritdoc}
   *
   * @todo remove BC-shim when all gateways are converted to plugins.
   */
  public function findDefinitions() {
    // Get discovered plugin definitions.
    $definitions = parent::findDefinitions();

    // Add hook_gateway_info definitions.
    foreach ($this->moduleHandler->invokeAll('gateway_info') as $id => $hook_info) {
      // @todo This allows overwriting of annotated plugins by hook plugins.
      // @todo Is that acceptable?
      $definitions[$id] = [
        'id' => $id,
        'label' => new TranslationWrapper($hook_info['name']),
        'configurable' => is_callable($hook_info['configure form']),
        'class' => '\Drupal\sms\Gateway\HookGateway',
        'provider' => 'sms',
        'hook_info' => $hook_info,
      ];
    }
    return $definitions;
  }

}
