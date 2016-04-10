<?php

/**
 * @file
 * Contains \Drupal\sms\Provider\DefaultSmsProvider
 */

namespace Drupal\sms\Provider;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;
use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\Entity\SmsGatewayInterface;
use Drupal\sms\Entity\SmsMessageInterface as SmsMessageEntityInterface;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResultInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\sms\Exception\SmsException;

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
  public function queue(SmsMessageEntityInterface $sms_message) {
    $gateway = $this->getGateway($sms_message);
    if ($gateway->getSkipQueue()) {
      $this->send($sms_message, []);
      return;
    }

    if (!$sms_message->getGateway()) {
      // @fixme getGateway being falsy is undocumented...
      $sms_message->setGateway($this->getDefaultGateway());
    }

    if ($count = $sms_message->validate()->count()) {
      throw new SmsException(sprintf('Can not queue SMS message because there are %s validation error(s).', $count));
    }

    // Split messages to overcome gateway limits.
    $max = $gateway->getMaxRecipientsOutgoing();
    $recipients_all = $sms_message->getRecipients();
    if ($max > 0 && count($recipients_all) > $max) {
      foreach ($sms_message->chunkByRecipients($max) as $sms_message) {
        $sms_message->save();
      }
    }
    else {
      $sms_message->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function queueIn(SmsMessageInterface $sms_message) {
    $this->plainMessageQueue($sms_message, SmsMessageEntityInterface::DIRECTION_INCOMING);
  }

  /**
   * {@inheritdoc}
   */
  public function queueOut(SmsMessageInterface $sms_message) {
    $this->plainMessageQueue($sms_message, SmsMessageEntityInterface::DIRECTION_OUTGOING);
  }

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms, array $options = array()) {
    $gateway = $this->getGateway($sms);
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
  protected function process(SmsMessageInterface $sms, array $options, SmsGatewayInterface $sms_gateway) {
    // Ensure that the delivery report route is set here.
    if (!isset($options['delivery_report_url'])) {
      $options['delivery_report_url'] = Url::fromRoute('sms.process_delivery_report', ['sms_gateway' => $sms_gateway->id()], ['absolute' => TRUE])->toString();
    }
    return $sms_gateway->getPlugin()
      ->send($sms, $options);
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
  public function receipt(array $reports, array $options = []) {
    // @todo Implement rules event integration here delivery report receipts.
    // Execute three phases.
    $this->moduleHandler->invokeAll('sms_receipt', array('pre process', $reports, $options));
    $this->moduleHandler->invokeAll('sms_receipt', array('process', $reports, $options));
    $this->moduleHandler->invokeAll('sms_receipt', array('post process', $reports, $options));
  }

  /**
   * {@inheritdoc}
   */
  public function processDeliveryReport(Request $request, SmsGatewayInterface $sms_gateway, array $options = []) {
    // The response that will be sent back to the server API. The gateway plugin
    // can alter this response as needed.
    $response = new Response('');
    $reports = $sms_gateway->getPlugin()
      ->parseDeliveryReports($request, $response);
    // Invoke the delivery report hook so other modules can alter the response.
    $this->moduleHandler->invokeAll('sms_delivery_report', [$reports, $response]);
    return $response;
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

  /**
   * Get the gateway for a SMS message.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms_message
   *   A SMS message.
   *
   * @return \Drupal\sms\Entity\SmsGatewayInterface
   *   A SMS Gateway config entity.
   */
  protected function getGateway(SmsMessageInterface $sms_message) {
    if (isset($options['gateway'])) {
      $gateway = SmsGateway::load($options['gateway']);
    }
    else if ($sms_message instanceof SmsMessageEntityInterface) {
      $gateway = $sms_message->getGateway();
    }

    return !empty($gateway) ? $gateway : $this->getDefaultGateway();
  }

  /**
   * Queues a standard SMS message object and converts it to an entity.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms_message
   *   A standard SMS message object.
   * @param $direction
   *   Value of SmsMessageEntityInterface::DIRECTION_* constants.
   */
  protected function plainMessageQueue(SmsMessageInterface $sms_message, $direction) {
    $gateway = $this->getGateway($sms_message);
    if ($gateway->getSkipQueue() && $direction == SmsMessageEntityInterface::DIRECTION_OUTGOING) {
      $this->send($sms_message, []);
      return;
    }

    // Convert SMS message to an entity.
    $sms_message = SmsMessage::convertFromSmsMessage($sms_message);

    // @fixme add a direction method?
    $sms_message->set('direction', $direction);

    $this->queue($sms_message);
  }

}
