<?php

/**
 * @file
 * Contains \Drupal\sms\Gateway\HookGateway
 */

namespace Drupal\sms\Gateway;

use Drupal\Core\Form\FormStateInterface;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;
use Symfony\Component\HttpFoundation\Request;

/**
 * BC-shim class to allow hook_gateway_info() based gateways to still work.
 */
class HookGateway extends GatewayBase {

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms, array $options) {
    $hook = $this->pluginDefinition['hook_info']['send'];
    if (is_callable($hook)) {
      $result = $hook($sms, $options);
      return new SmsMessageResult($result);
    }
    throw new \BadMethodCallException(sprintf('No send method defined for gateway %s', $this->configuration['name']));
  }

  /**
   * {@inheritdoc}
   */
  public function balance() {
    $hook = $this->pluginDefinition['hook_info']['balance'];
    if (is_callable($hook)) {
      return $hook();
    }
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function deliveryReport(Request $request) {
    $hook = $this->pluginDefinition['hook_info']['delivery report'];
    if (is_callable($hook)) {
      return $hook();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $hook = $this->pluginDefinition['hook_info']['configure form'];
    if (is_callable($hook)) {
      return array_merge($form, $hook($this->getCustomConfiguration()));
    }
    else {
      throw new \RuntimeException($this->t('Configuration form callback not available.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $hook = $this->pluginDefinition['hook_info']['configure form'] . '_validate';
    if (is_callable($hook)) {
      $hook($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $hook = $this->pluginDefinition['hook_info']['configure form'] . '_submit';
    if (is_callable($hook)) {
      // Call the hook gateway submit callback.
      $hook($form, $form_state);
    }
    // Update the configuration.
    $this->setCustomConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function sendForm(array &$form, FormStateInterface $form_state) {
    $hook = $this->pluginDefinition['hook_info']['send form'];
    if (is_callable($hook)) {
      return $hook($form, $form_state);
    }
    else {
      return array();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateNumbers(array $numbers, array $options = array()) {
    $hook = $this->pluginDefinition['hook_info']['validate number'];
    if (is_callable($hook)) {
      return $hook($numbers, $options);
    }
    else {
      return array();
    }
  }

}
