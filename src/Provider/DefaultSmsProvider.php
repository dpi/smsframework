<?php

/**
 * @file
 * Contains \Drupal\sms\Provider\DefaultSmsProvider
 */

namespace Drupal\sms\Provider;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\Gateway\GatewayInterface;
use Drupal\sms\Gateway\GatewayManagerInterface;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResultInterface;
use Drupal\sms\SmsException;
use Drupal\sms\SmsGatewayInterface;

/**
 * The SMS provider that provides default messaging functionality.
 */
class DefaultSmsProvider implements SmsProviderInterface {

  /**
   * The SMS gateway manager.
   *
   * @var \Drupal\sms\Gateway\GatewayManager
   */
  protected $gatewayManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Creates a new instance of the default SMS provider.
   *
   * @param \Drupal\sms\Gateway\GatewayManagerInterface
   *   The gateway manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  public function __construct(GatewayManagerInterface $gateway_manager, ModuleHandlerInterface $module_handler) {
    $this->gatewayManager = $gateway_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms, array $options = array()) {
    // Check if a preferred gateway is specified in the $options.
    if (isset($options['gateway'])) {
      $gateway = SmsGateway::load($options['gateway']);
    }
    if (empty($gateway)) {
      $gateway = $this->gatewayManager->getDefaultGateway();
    }

    if ($this->preProcess($sms, $options, $gateway)) {
      $this->moduleHandler->invokeAll('sms_send', [$sms, $options, $gateway]);
      // @todo Apply token replacements.
      $result = $this->process($sms, $options, $gateway);
      $this->postProcess($sms, $options, $gateway, $result);
      return $result;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Processes the SMS message and returns the response from the gateway.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms
   *   The SMS to be sent.
   * @param array $options
   *   The gateway options.
   * @param \Drupal\sms\Gateway\GatewayInterface $gateway
   *   The default gateway for sending this message.
   *
   * @return \Drupal\sms\Message\SmsMessageResultInterface
   *   The message result from the gateway.
   */
  protected function process(SmsMessageInterface $sms, array $options, SmsGatewayInterface $sms_gateway) {
    $instance = $sms_gateway->getPlugin();
    return $instance->send($sms, $options);
  }

  /**
   * Calls pre-process hooks and ensures that the action is still permitted.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms
   *   The SMS to be sent.
   * @param array $options
   *   Additional options to be passed to the SMS gateway.
   * @param \Drupal\sms\Gateway\GatewayInterface $gateway
   *   The default gateway for sending this message.
   *
   * @return bool|null
   *   Whether to continue sending or not.
   */
  protected function preProcess(SmsMessageInterface $sms, array $options, SmsGatewayInterface $sms_gateway) {
    // Call the send pre process hooks.
    $return = $this->moduleHandler->invokeAll('sms_send_process', ['pre process', $sms, $options, $sms_gateway, NULL]);
    // Return FALSE if any of the hooks returned FALSE.
    return !in_array(FALSE, $return, TRUE);
  }

  /**
   * Calls post process hooks.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms
   *   The SMS that was sent.
   * @param array $options
   *   Additional options that were passed to the SMS gateway.
   * @param \Drupal\sms\Gateway\GatewayInterface $gateway
   *   The default gateway for sending this message.
   * @param \Drupal\sms\Message\SmsMessageResultInterface $result
   *   The message result from the gateway.
   */
  protected function postProcess(SmsMessageInterface $sms, array $options, SmsGatewayInterface $gateway, SmsMessageResultInterface $result) {
    // Call the send post process hooks.
    $this->moduleHandler->invokeAll('sms_send_process', ['post process', $sms, $options, $gateway, $result]);
  }

  /**
   * {@inheritdoc}
   */
  public function incoming(SmsMessageInterface $sms, array $options) {
    // @todo Implement rules event integration here for incoming SMS.
    // Execute three phases.
    $this->moduleHandler->invokeAll('sms_incoming', array('pre process', $sms, $options));
    $this->moduleHandler->invokeAll('sms_incoming', array('process', $sms, $options));
    $this->moduleHandler->invokeAll('sms_incoming', array('post process', $sms, $options));
  }

  /**
   * {@inheritdoc}
   */
  public function receipt($number, $reference, $message_status = GatewayInterface::STATUS_UNKNOWN, array $options = array()) {
    // @todo Implement rules event integration here for incoming SMS.
    // Execute three phases.
    $this->moduleHandler->invokeAll('sms_receipt', array('pre process', $number, $reference, $message_status, $options));
    $this->moduleHandler->invokeAll('sms_receipt', array('process', $number, $reference, $message_status, $options));
    $this->moduleHandler->invokeAll('sms_receipt', array('post process', $number, $reference, $message_status, $options));
  }

}
