<?php

/**
 * @file
 * Contains \Drupal\sms\Form\GatewayCreateForm
 */

namespace Drupal\sms\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sms\Gateway\GatewayManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GatewayAddForm extends FormBase {

  /**
   * The gateway manager.
   *
   * @var \Drupal\sms\Gateway\GatewayManagerInterface
   */
  protected $gatewayManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\sms\Gateway\GatewayManagerInterface $gateway_manager
   *   The gateway manager
   */
  public function __construct(ConfigFactoryInterface $config_factory, GatewayManagerInterface $gateway_manager) {
    $this->setConfigFactory($config_factory);
    $this->gatewayManager = $gateway_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.sms_gateway')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gateway_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gateway name'),
    ];
    $form['name'] = [
      '#type' => 'machine_name',
      '#title' => '',
      '#machine_name' => [
        'exists' => [$this, 'gatewayExists'],
        'source' => ['label'],
      ],
    ];
    $form['plugin_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Gateway type'),
      // List plugins that are configurable so user can choose.
      '#options' => array_filter(array_map(function ($definition) {
        return $definition['configurable'] ? $definition['label'] : FALSE;
      }, $this->gatewayManager->getGatewayPlugins())),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Create the gateway and redirect to the configure form.
    $configuration = $form_state->cleanValues()->getValues();
    $this->gatewayManager->addGateway($configuration['plugin_id'], $configuration);
    $form_state->setRedirect('sms.gateway_config', ['gateway_id' => $configuration['name']]);
  }

  /**
   * Helper function for machine_name form element.
   */
  public function gatewayExists($machine_name) {
    return (bool) $this->gatewayManager->getGateway($machine_name);
  }

}
