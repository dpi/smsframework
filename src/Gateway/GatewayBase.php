<?php

/**
 * @file
 * Contains \Drupal\sms\Gateway\GatewayBase
 */

namespace Drupal\sms\Gateway;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base class for sms gateway plugins.
 */
abstract class GatewayBase extends PluginBase implements GatewayInterface {

  /**
   * The watchdog logger for this gateway.
   *
   * @var \Psr\Log\LoggerInterface.
   */
  protected $logger;

  /**
   * Construct a new SmsGateway plugin
   *
   * @param array $configuration
   *   The configuration to use and build the sms gateway.
   * @param string $plugin_id
   *   The gateway id.
   * @param mixed $plugin_definition
   *   The gateway plugin definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration = array_merge($this->defaultConfiguration(), $this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getIdentifier() {
    return $this->configuration['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->configuration['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->configuration['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomConfiguration() {
    return $this->configuration['custom'];
  }

  /**
   * {@inheritdoc}
   */
  public function setCustomConfiguration($configuration) {
    $this->configuration['custom'] = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'plugin_id' => $this->pluginDefinition['id'],
      'name' => $this->pluginDefinition['id'],
      'label' => (string) $this->pluginDefinition['label'],
      'enabled' => FALSE,
      'custom' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigurable() {
    return $this->pluginDefinition['configurable'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    return;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    return;
  }

  /**
   * {@inheritdoc}
   */
  public function sendForm(array &$form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return isset($this->configuration['enabled']) ? $this->configuration['enabled'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setEnabled($enabled = TRUE) {
    $this->configuration['enabled'] = (bool) $enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateNumbers(array $numbers, array $options = []) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function balance() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function deliveryReport(Request $request) {
    return;
  }

  /**
   * {@inheritdoc}
   */
  public function getError() {
    return [];
  }

  /**
   * Gets the Psr logger for this gateway.
   */
  protected function logger() {
    if (!isset($this->logger)) {
      $this->logger = \Drupal::logger($this->getName());
    }
    return $this->logger;
  }

}
