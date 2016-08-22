<?php

/**
 * @file
 * Contains \Drupal\sms\Provider\DefaultSmsProvider
 */

namespace Drupal\sms\Provider;

use Drupal\sms\Event\SmsMessageProcessedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\Entity\SmsGatewayInterface;
use Drupal\sms\Entity\SmsMessageInterface as SmsMessageEntityInterface;
use Drupal\sms\Event\SmsMessageEvent;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Plugin\SmsGatewayPluginIncomingInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\sms\Exception\SmsException;
use Drupal\sms\Direction;
use Drupal\sms\Event\SmsEvents;
use Drupal\sms\Message\SmsMessageResultInterface;

/**
 * The SMS provider that provides default messaging functionality.
 */
class DefaultSmsProvider implements SmsProviderInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

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
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface
   *   The gateway manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    $this->eventDispatcher = $event_dispatcher;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function queue(SmsMessageInterface $sms_message) {
    $sms_messages = $this->dispatch(SmsEvents::MESSAGE_PRE_PROCESS, [$sms_message])->getMessages();
    $sms_messages = $this->dispatch(SmsEvents::MESSAGE_QUEUE_PRE_PROCESS, $sms_messages)->getMessages();

    foreach ($sms_messages as $gateway_id => &$sms_message) {
      // Tag so SmsEvents::MESSAGE_PRE_PROCESS is not dispatched again.
      $sms_message->setOption('_skip_preprocess_event', TRUE);

      if ($sms_message instanceof SmsMessageEntityInterface && ($count = $sms_message->validate()->count())) {
        throw new SmsException(sprintf('Can not queue SMS message because there are %s validation error(s).', $count));
      }

      if ($sms_message->getGateway()->getSkipQueue()) {
        switch ($sms_message->getDirection()) {
          case Direction::INCOMING:
            $this->incoming($sms_message);
            break;
          case Direction::OUTGOING:
            $this->send($sms_message);
            break;
        }
        continue;
      }

      $sms_message = SmsMessage::convertFromSmsMessage($sms_message);
      $sms_message->save();
    }

    // Queue has different post-process events because there is no result.
    return $this->dispatch(SmsEvents::MESSAGE_QUEUE_POST_PROCESS, $sms_messages)->getMessages();
  }

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms) {
    $dispatch = !$sms->getOption('_skip_preprocess_event');
    $sms_messages = $dispatch ? $this->dispatch(SmsEvents::MESSAGE_PRE_PROCESS, [$sms])->getMessages() : [$sms];
    $sms_messages = $this->dispatch(SmsEvents::MESSAGE_OUTGOING_PRE_PROCESS, $sms_messages)->getMessages();

    $results = [];
    foreach ($sms_messages as &$sms_message) {
      $plugin = $sms_message->getGateway()->getPlugin();

      $result = $plugin->send($sms_message);
      $result->setMessages([$sms_message]);
      $results[] = $result;

      $this->dispatch(SmsEvents::MESSAGE_OUTGOING_POST_PROCESS, [$sms_message], $result);
      $this->dispatch(SmsEvents::MESSAGE_POST_PROCESS, [$sms_message], $result);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function incoming(SmsMessageInterface $sms_message) {
    $dispatch = !$sms_message->getOption('_skip_preprocess_event');
    $sms_messages = $dispatch ? $this->dispatch(SmsEvents::MESSAGE_PRE_PROCESS, [$sms_message])->getMessages() : [$sms_message];
    $sms_messages = $this->dispatch(SmsEvents::MESSAGE_INCOMING_PRE_PROCESS, $sms_messages)->getMessages();

    $results = [];
    foreach ($sms_messages as &$sms_message) {
      $plugin = $sms_message->getGateway()->getPlugin();
      if (!$plugin instanceof SmsGatewayPluginIncomingInterface) {
        throw new SmsException(sprintf('Gateway plugin `%s` does not support incoming messages', $plugin->getPluginId()));
      }

      $result = $plugin->incoming($sms_message);
      $result->setMessages([$sms_message]);
      $results[] = $result;

      $this->dispatch(SmsEvents::MESSAGE_INCOMING_POST_PROCESS, [$sms_message], $result);
      $this->dispatch(SmsEvents::MESSAGE_POST_PROCESS, [$sms_message], $result);
    }

    return $results;
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
   * @return \Drupal\sms\Entity\SmsGatewayInterface|NULL
   *   A SmsGateway config entity, or NULL if default gateway is not set or
   *   invalid.
   */
  public function getDefaultGateway() {
    $gateway_id = $this->configFactory
      ->get('sms.settings')
      ->get('default_gateway');
    return $gateway_id ? SmsGateway::load($gateway_id) : NULL;
  }

  /**
   * Sets the Gateway that will be used by default to send SMS.
   *
   * @param \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway|NULL
   *   The new site default SMS Gateway, or NULL to unset.
   */
  public function setDefaultGateway(SmsGatewayInterface $sms_gateway = NULL) {
    $default_gateway = $sms_gateway ? $sms_gateway->id() : NULL;
    $this->configFactory
      ->getEditable('sms.settings')
      ->set('default_gateway', $default_gateway)
      ->save();
  }

  /**
   * Dispatch an event for messages.
   *
   * @param string $event_name
   *   The event to trigger.
   * @param \Drupal\sms\Message\SmsMessageInterface[] $sms_messages
   *   The messages to dispatch.
   * @param \Drupal\sms\Message\SmsMessageResultInterface $result
   *   (optional) an SMS result to use on post process events.
   *
   * @return \Drupal\sms\Event\SmsMessageEvent|\Drupal\sms\Event\SmsMessageProcessedEvent
   */
  protected function dispatch($event_name, array $sms_messages, SmsMessageResultInterface $result = NULL) {
    if ($result) {
      $event = new SmsMessageProcessedEvent($sms_messages);
      $event->setResults([$result]);
    }
    else {
      $event = new SmsMessageEvent($sms_messages);
    }

    $event = $this->eventDispatcher
      ->dispatch($event_name, $event);

    return $event;
  }

}
