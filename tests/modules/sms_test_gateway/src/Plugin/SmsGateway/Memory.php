<?php

/**
 * @file
 * Contains \Drupal\sms_test_gateway\Plugin\SmsGateway\Memory
 */

namespace Drupal\sms_test_gateway\Plugin\SmsGateway;

use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a gateway storing transmitted SMS in memory.
 *
 * @SmsGateway(
 *   id = "memory",
 *   label = @Translation("Memory"),
 * )
 */
class Memory extends SmsGatewayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'widget' => '',
      // Store the ID of gateway config. See static::send().
      'gateway_id' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['widget'] = array(
      '#type' => 'textfield',
      '#title' => t('Widget'),
      '#description' => t('Enter a widget.'),
      '#default_value' => $config['widget'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['widget'] = $form_state->getValue('widget');
  }

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms_message, array $options) {
    $state = \Drupal::state()->get('sms_test_gateway.memory.send', []);

    $gateway_id = $this->configuration['gateway_id'];
    $state[$gateway_id][] = $sms_message;
    \Drupal::state()->set('sms_test_gateway.memory.send', $state);

    return new SmsMessageResult(['status' => TRUE]);
  }

  public function validateNumber($numbers) {
    $errors = array();
    foreach ($numbers as $number) {
      $code = substr($number, 0, 3);
      if (preg_match('/[^0-9]/', $number)) {
        $errors[] = t('Non-numeric character found in number.');
      }
      if (strlen($number) > 15 || strlen($number) < 10) {
        $errors[] = t('Number longer than 15 digits or shorter than 10 digits.');
      }
      if ($code == '990' || $code == '997' || $code == '999') {
        $errors[] = t('Country code not allowed');
      }
    }
    return $errors;
  }

}
