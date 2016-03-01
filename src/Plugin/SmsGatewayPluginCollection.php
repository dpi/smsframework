<?php

/**
 * @file
 * Contains \Drupal\sms\Plugin\SmsGatewayPluginCollection
 */

namespace Drupal\sms\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Provides a container for lazily loading SMS Gateway plugin instances.
 */
class SmsGatewayPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The machine name of the gateway config entity using this plugin collection.
   *
   * @var string
   */
  protected $gatewayName;

  /**
   * Constructs a new SmsGatewayPluginCollection.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating SMS plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param string $gateway_name
   *   The machine name of the gateway config entity.
   * @param array $configuration
   *   An array of configuration.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, $gateway_name, array $configuration) {
    parent::__construct($manager, $instance_id, $configuration);

    $this->gatewayName = $gateway_name;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    parent::initializePlugin($instance_id);

    $plugin_instance = $this->pluginInstances[$instance_id];
    if ($plugin_instance instanceof SmsGatewayPluginInterface) {
      $plugin_instance->setGatewayName($this->gatewayName);
    }
  }

}
