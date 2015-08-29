<?php

/**
 * @file
 * Contains \Drupal\sms\Form\GatewayConfigForm
 */

namespace Drupal\sms\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sms\Gateway\GatewayManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a configuration form for sms gateways.
 *
 * @TODO Implementing Gateways as Entities or Plugins would make this config
 * form more streamlined
 */
class GatewayConfigForm extends ConfigFormBase {

  /**
   * The gateway manager.
   *
   * @var \Drupal\sms\Gateway\GatewayManagerInterface
   */
  protected $gatewayManager;

  /**
   * Creates new Gateway configuration form.
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
  public function getFormID() {
    return 'sms_admin_gateway_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $gateway_id = NULL) {
    $gateway = $this->gatewayManager->getGateway($gateway_id);
    if ($gateway && $gateway->isConfigurable()) {
      $form['title'] = [
        '#type' => 'item',
        '#title' => $gateway->getLabel(),
        '#markup' => '(' . $gateway->getName() . ')',
      ];
      $form['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable this gateway'),
        '#default_value' => $gateway->isEnabled(),
      ];
      $form = $gateway->buildConfigurationForm($form, $form_state);
      $form['#title'] = $this->t('@gateway configuration', array('@gateway' => $gateway->getLabel()));
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Save configuration'),
      );
      $form['gateway_id'] = array(
        '#type' => 'value',
        '#value' => $gateway->getIdentifier(),
      );
  
      return $form;
    }
    else {
      throw new NotFoundHttpException();
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Pass validation to gateway.
    $gateway = $this->gatewayManager->getGateway($form_state->getValue('gateway_id'));
    $gateway->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Call the gateway submission callback before saving configuration.
    $gateway = $this->gatewayManager->getGateway($form_state->getValue('gateway_id'));
    $gateway->setEnabled($form_state->getValue('enabled'));
    $form_state
      ->unsetValue('title')
      ->unsetValue('enabled')
      ->unsetValue('gateway_id')
      ->cleanValues();
    $gateway->submitConfigurationForm($form, $form_state);
    $this->gatewayManager->saveGateway($gateway);
    drupal_set_message($this->t('The gateway settings have been saved.'));
    $form_state->setRedirect('sms.gateway_admin');
  }

  /**
   * Title callback fo the menu
   */
  public function getTitle($gateway_id) {
    return $this->t('@name gateway', ['@name' => $this->gatewayManager->getGateway($gateway_id)->getLabel()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

}
