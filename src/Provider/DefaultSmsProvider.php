<?php

/**
 * @file
 * Contains \Drupal\sms\Provider\DefaultSmsProvider
 */

namespace Drupal\sms\Provider;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageResultInterface;
use Drupal\sms\SmsException;

/**
 * The SMS provider that provides default messaging functionality.
 */
class DefaultSmsProvider implements SmsProviderInterface {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   *
   * The module handler.
   */
  protected $moduleHandler;

  /**
   * Creates a new instance of the default SMS provider.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   *
   * @TODO An effective method of cascading messages and errors back up from the
   *   gateways.
   */
  public function send(SmsMessageInterface $sms, array $options = array()) {
    // Check if a preferred gateway is specified in the $options.
    if (isset($options['gateway'])) {
      $gateway_id = $options['gateway'];
    }
    if (empty($gateway_id)) {
      $gateway_id = sms_default_gateway_id();
    }
    $gateway = sms_gateways('gateway', $gateway_id);

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
   * Processes the SMS message and handles the response from the gateway.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms
   *   The SMS to be sent.
   * @param array $options
   *   The gateway options.
   * @param array $gateway
   *   The default gateway for sending this message.
   *
   * @return bool
   *   TRUE if the message was successfully sent.
   */
  protected function process(SmsMessageInterface $sms, array $options, array $gateway) {
    $response = new SmsMessageResult($gateway['send']($sms, $options));
    $result = $this->handleResult($response, $sms);
    return $result;
  }

  /**
   * Calls pre-process hooks and ensures that the action is still permitted.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms
   *   The SMS to be sent.
   * @param array $options
   *   Additional options to be passed to the SMS gateway.
   * @param array $gateway
       The default gateway for sending this message.
   *
   * @returns bool
   *   Whether to continue sending or not.
   */
  protected function preProcess(SmsMessageInterface $sms, array $options, array $gateway) {
    // Call the send pre process hooks.
    $return = $this->moduleHandler->invokeAll('sms_send_process', ['pre process', $sms, $options, $gateway, NULL]);
    // Return FALSE if any of the hooks returned FALSE.
    return !in_array(FALSE, $return);
  }

  /**
   * Calls post process hooks.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms
   *   The SMS that was sent.
   * @param array $options
   *   Additional options that were passed to the SMS gateway.
   * @param array $gateway
   *   The default gateway for sending this message.
   * @param bool $result
   *   Whether the SMS sending was successful or not.
   */
  protected function postProcess(SmsMessageInterface $sms, array $options, array $gateway, $result) {
    // Call the send post process hooks.
    $this->moduleHandler->invokeAll('sms_send_process', ['post process', $sms, $options, $gateway, $result]);
  }

  /**
   * Handles the response back from the SMS gateway.
   *
   * @param \Drupal\sms\Message\SmsMessageResultInterface $result
   *   The result to be handled.
   * @param \Drupal\sms\Message\SmsMessageInterface $sms
   *   The message that was sent.
   *
   * @return bool
   *   True if message was sent successfully. Throws an SmsException if message
   *   sending failed.
   *
   * @throws \Drupal\sms\SmsException
   */
  protected function handleResult(SmsMessageResultInterface $result, SmsMessageInterface $sms) {
    if ($result->getStatus()) {
      return TRUE;
    }
    else {
      // @todo Review all of this.
      $error_message = t('Sending SMS to %number failed.', ['%number' => implode(',', $sms->getRecipients())]);
      if ($message = $result->getErrorMessage()) {
        $error_message .= t(' The gateway said %message.', ['%message' => $message]);
      }
      \Drupal::logger('sms')->error($message);
      throw new SmsException($error_message);
    }
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
  public function receipt($number, $reference, $message_status = SMS_GW_UNKNOWN_STATUS, $options = array()) {
    // @todo Implement rules event integration here for incoming SMS.
    // Execute three phases.
    $this->moduleHandler->invokeAll('sms_receipt', array('pre process', $number, $reference, $message_status, $options));
    $this->moduleHandler->invokeAll('sms_receipt', array('process', $number, $reference, $message_status, $options));
    $this->moduleHandler->invokeAll('sms_receipt', array('post process', $number, $reference, $message_status, $options));
  }

}
