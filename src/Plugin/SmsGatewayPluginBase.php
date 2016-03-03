<?php

/**
 * @file
 * Contains \Drupal\sms\Plugin\SmsGatewayPluginBase
 */

namespace Drupal\sms\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base class for sms gateway plugins.
 */
abstract class SmsGatewayPluginBase extends PluginBase implements SmsGatewayPluginInterface {

  /**
   * The watchdog logger for this gateway.
   *
   * @var \Psr\Log\LoggerInterface.
   */
  protected $logger;

  /**
   * The machine name of the gateway config entity owning this plugin instance.
   *
   * @var string
   */
  protected $gatewayName;

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
  public function setGatewayName($machine_name) {
    $this->gatewayName = $machine_name;
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
  public function defaultConfiguration() {
    return [];
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
  public function parseDeliveryReports(Request $request, Response $response) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function pullDeliveryReports(array $message_ids = NULL) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getError() {
    return [];
  }

  public function getDeliveryReportPath($absolute = TRUE) {
    if (isset($this->gatewayName)) {
      return Url::fromRoute('sms.delivery_report', ['gateway_name' => $this->gatewayName], ['absolute' => $absolute])->toString();
    }
    else {
      return '';
    }
  }

  /**
   * Gets the Psr logger for this gateway.
   */
  protected function logger() {
    if (!isset($this->logger)) {
      $definition = $this->getPluginDefinition();
      $this->logger = \Drupal::logger($definition['provider'] . '.' . $definition['id'] );
    }
    return $this->logger;
  }

}
