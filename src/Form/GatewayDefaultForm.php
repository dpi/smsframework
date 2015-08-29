<?php

/**
 * @file
 * Contains \Drupal\sms\Form\GatewayDefaultForm
 */

namespace Drupal\sms\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\sms\Gateway\GatewayManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration form for setting the default gateway.
 */
class GatewayDefaultForm extends ConfigFormBase {

  /**
   * The gateway manager.
   *
   * @var \Drupal\sms\Gateway\GatewayManagerInterface
   */
  protected $gatewayManager;

  /**
   * Creates new Gateway default selection form.
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
    return 'sms_admin_default_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    /** @var \Drupal\sms\Gateway\GatewayInterface[] $gateways */
    $gateways = $this->gatewayManager->getAvailableGateways();
    $default = $this->gatewayManager->getDefaultGateway();

    $form['gateways'] = array(
      '#type' => 'table',
      '#header' => array(
        $this->t('Enabled'),
        $this->t('Default'),
        $this->t('Name'),
        array(
          'data' => t('Operations'),
          'colspan' => 1,
        )
      ),
      '#attributes' => array(
        'id' => 'table-' . $this->getFormID(),
      ),
    );
    foreach ($gateways as $identifier => $gateway) {
      $form['gateways'][$identifier] = array(
        'enabled' => [
          '#type' => 'checkbox',
          '#default_value' => $gateway->isEnabled(),
          // The checkbox should be disabled if this gateway is the default.
          // @todo Needs tests.
          '#states' => [
            'disabled' => [
              ':input[name="default"]' => ['value' => $identifier],
            ],
          ],
        ],
        'default' => [
          '#name' => 'default',
          '#type' => 'radio',
          '#default_value' => ($default && $default->getName() ==  $identifier),
          '#return_value' => $identifier,
          // The radio button should be disabled if this gateway not enabled.
          // @todo Needs tests.
          '#states' => [
            'disabled' => [
              ':input[name="gateways[' . $identifier . '][enabled]"]' => ['checked' => FALSE],
            ],
          ],
        ],
        'name' => [
          '#markup' => SafeMarkup::checkPlain($gateway->getLabel()),
        ],
      );
      if ($gateway->isConfigurable()) {
        $form['gateways'][$identifier]['configure'] = [
          '#type' => 'link',
          '#title' => $this->t('configure'),
          '#url' => Url::fromRoute('sms.gateway_config', ['gateway_id' => $identifier]),
        ];
      }
      else {
        $form['gateways'][$identifier]['configure'] = [
          '#markup' => $this->t('No configuration options')
        ];
      }
    }
    $form['actions']['submit']['#value'] = $this->t('Save settings');
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getUserInput()['default'])) {
      $form_state->setErrorByName('default', $this->t('Default gateway must be set.'));
    }
    else {
      $form_state->setValue('default', $form_state->getUserInput()['default']);
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Set the enabled status of the gateways.
    $new_default = $form_state->getValue('default');
    foreach ($form_state->getValue('gateways') as $identifier => $config) {
      $gateway = $this->gatewayManager->getGateway($identifier);
      // Selected default gateway is automatically enabled.
      if ($gateway->getName() === $new_default) {
        $config['enabled'] = TRUE;
      }
      $gateway->setEnabled($config['enabled']);
      $this->gatewayManager->saveGateway($gateway);
    }
    // Process form submission to set the default gateway.
    if ($this->gatewayManager->getGateway($new_default)) {
      drupal_set_message($this->t('Default gateway updated.'));
      $this->gatewayManager->setDefaultGateway($new_default);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sms.settings'];
  }

}
