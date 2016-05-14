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
use Drupal\sms\Plugin\SmsGatewayPluginIncomingInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\sms\Exception\SmsException;
use Drupal\sms\Direction;

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
  public function queue(SmsMessageEntityInterface &$sms_message) {
    if (!$sms_message->getGateway()) {
      $sms_message->setGateway($this->getDefaultGateway());
    }

    if ($sms_message->getGateway()->getSkipQueue()) {
      switch ($sms_message->getDirection()) {
        case Direction::INCOMING:
          $this->incoming($sms_message);
          return;
        case Direction::OUTGOING:
          $this->send($sms_message);
          return;
      }
      return;
    }

    if ($count = $sms_message->validate()->count()) {
      throw new SmsException(sprintf('Can not queue SMS message because there are %s validation error(s).', $count));
    }

    // Split messages to overcome gateway limits.
    $max = $sms_message->getGateway()->getMaxRecipientsOutgoing();
    foreach ($sms_message->chunkByRecipients($max) as $sms_message) {
      $sms_message->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function queueIn(SmsMessageInterface $sms_message) {
    $this->plainMessageQueue($sms_message, Direction::INCOMING);
  }

  /**
   * {@inheritdoc}
   */
  public function queueOut(SmsMessageInterface $sms_message) {
    $this->plainMessageQueue($sms_message, Direction::OUTGOING);
  }

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms) {
    if (!$sms->getGateway()) {
      $sms->setGateway($this->getDefaultGateway());
    }

    $results = [];
    $max = $sms->getGateway()->getMaxRecipientsOutgoing();
    foreach ($sms->chunkByRecipients($max) as $sms_message) {
      if ($this->preProcess($sms_message)) {
        $this->moduleHandler->invokeAll('sms_send', [$sms_message]);
        $result = $this->process($sms_message);
        $this->postProcess($sms_message, $result);
        $results[] = $result;
      }
    }

    return $results;
  }

  /**
   * Processes the SMS message and returns the response from the gateway.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms
   *   The SMS to be sent.
   *
   * @return \Drupal\sms\Message\SmsMessageResultInterface
   *   The message result from the gateway.
   */
  protected function process(SmsMessageInterface $sms) {
    if (!$delivery_report_url = $sms->getOption('delivery_report_url')) {
      $url = Url::fromRoute('sms.process_delivery_report', ['sms_gateway' => $sms->getGateway()->id()])
        ->setAbsolute()->toString();
      $sms->setOption('delivery_report_url', $url);
    }
    return $sms->getGateway()
      ->getPlugin()
      ->send($sms);
  }

  /**
   * Calls pre-process hooks and ensures that the action is still permitted.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms
   *   The SMS to be sent.
   *
   * @return bool|null
   *   Whether to continue sending or not.
   */
  protected function preProcess(SmsMessageInterface $sms) {
    $return = $this->moduleHandler->invokeAll('sms_send_process', ['pre process', $sms, NULL]);
    // Return FALSE if any of the hooks returned FALSE.
    return !in_array(FALSE, $return, TRUE);
  }

  /**
   * Calls post process hooks.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms
   *   The SMS that was sent.
   * @param \Drupal\sms\Message\SmsMessageResultInterface $result
   *   The message result from the gateway.
   */
  protected function postProcess(SmsMessageInterface $sms, SmsMessageResultInterface $result) {
    $this->moduleHandler->invokeAll('sms_send_process', ['post process', $sms, $result]);
  }

  /**
   * {@inheritdoc}
   */
  public function incoming(SmsMessageInterface $sms_message) {
    $this->moduleHandler->invokeAll('sms_incoming_preprocess', [$sms_message]);

    // Process the SMS message with the gateway plugin.
    if ($sms_message instanceof SmsMessageEntityInterface) {
      $plugin = $sms_message->getGateway()->getPlugin();
      if ($plugin instanceof SmsGatewayPluginIncomingInterface) {
        $plugin->incoming($sms_message);
      }
    }

    $this->moduleHandler->invokeAll('sms_incoming_postprocess', [$sms_message]);
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
   * Queues a standard SMS message object and converts it to an entity.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms_message
   *   A standard SMS message object.
   * @param $direction
   *   Value of SmsMessageEntityInterface::DIRECTION_* constants.
   */
  protected function plainMessageQueue(SmsMessageInterface $sms_message, $direction) {
    $gateway = $sms_message->getGateway() ?: $this->getDefaultGateway();

    if ($gateway->getSkipQueue() && $direction == Direction::OUTGOING) {
      $this->send($sms_message);
      return;
    }

    // Convert SMS message to an entity.
    $sms_message = SmsMessage::convertFromSmsMessage($sms_message)
      ->setDirection($direction);

    $this->queue($sms_message);
  }

}
