<?php

/**
 * @file
 * Contains \Drupal\sms\Provider\DefaultSmsProvider
 */

namespace Drupal\sms\Provider;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\Entity\SmsGatewayInterface;
use Drupal\sms\Plugin\SmsGatewayPluginInterface;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Entity\SmsMessageInterface as SmsMessageEntityInterface;
use Drupal\sms\Entity\SmsMessage as SmsMessageEntity;
use Drupal\sms\Message\SmsMessageResultInterface;

/**
 * The SMS provider that provides default messaging functionality.
 */
class DefaultSmsProvider implements SmsProviderInterface {

  /**
   * Configuration factory for this SMS provider.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Creates a new instance of the default SMS provider.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface
   *   The gateway manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms, array $options = []) {
    // Check if a preferred gateway is specified in the $options.
    if (isset($options['gateway'])) {
      $gateway = SmsGateway::load($options['gateway']);
    }
    if (empty($gateway)) {
      $gateway = $this->getDefaultGateway();
    }

    if (!$sms instanceof SmsMessageEntityInterface) {
      $original = $sms;
      /** @var \Drupal\sms\Entity\SmsMessageInterface $sms */
      $sms = SmsMessageEntity::create();
      $sms
        ->setMessage($original->getMessage())
        ->addRecipients($original->getRecipients())
        ->setAutomated($original->isAutomated())
        ->setGateway($gateway);
      foreach ($original->getOptions() as $name => $value) {
        $sms->setOption($name, $value);
      }
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
   * @param \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway
   *   The default gateway for sending this message.
   *
   * @return \Drupal\sms\Message\SmsMessageResultInterface
   *   The message result from the gateway.
   */
  public function process(SmsMessageInterface $sms, array $options, SmsGatewayInterface $sms_gateway) {
    if ($this->preProcess($sms, $options, $gateway)) {
      $result = $sms_gateway->getPlugin()
        ->send($sms, $options);
    }
    return FALSE;
  }

  /**
   * Calls pre-process hooks and ensures that the action is still permitted.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms
   *   The SMS to be sent.
   * @param array $options
   *   Additional options to be passed to the SMS gateway.
   * @param \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway
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
   * @param \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway
   *   The default gateway for sending this message.
   * @param \Drupal\sms\Message\SmsMessageResultInterface $result
   *   The message result from the gateway.
   */
  protected function postProcess(SmsMessageInterface $sms, array $options, SmsGatewayInterface $sms_gateway, SmsMessageResultInterface $result) {
    // Call the send post process hooks.
    $this->moduleHandler->invokeAll('sms_send_process', ['post process', $sms, $options, $sms_gateway, $result]);
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
  public function receipt($number, $reference, $message_status = SmsGatewayPluginInterface::STATUS_UNKNOWN, array $options = array()) {
    // @todo Implement rules event integration here for incoming SMS.
    // Execute three phases.
    $this->moduleHandler->invokeAll('sms_receipt', array('pre process', $number, $reference, $message_status, $options));
    $this->moduleHandler->invokeAll('sms_receipt', array('process', $number, $reference, $message_status, $options));
    $this->moduleHandler->invokeAll('sms_receipt', array('post process', $number, $reference, $message_status, $options));
  }

  /**
   * Gets the gateway that will be used by default for sending SMS.
   *
   * @return \Drupal\sms\Entity\SmsGatewayInterface|null
   *   A SmsGateway config entity, or NULL if default gateway is not set or
   *   invalid.
   */
  public function getDefaultGateway() {
    $gateway_id = $this->configFactory
      ->get('sms.settings')
      ->get('default_gateway');
    return SmsGateway::load($gateway_id);
  }

  /**
   * Sets the Gateway that will be used by default to send SMS.
   *
   * @param \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway
   *   The new site default SMS Gateway.
   */
  public function setDefaultGateway(SmsGatewayInterface $sms_gateway) {
    $this->configFactory
      ->getEditable('sms.settings')
      ->set('default_gateway', $sms_gateway->id())
      ->save();
  }

}
