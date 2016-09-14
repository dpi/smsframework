<?php

namespace Drupal\sms\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\sms\Entity\SmsGatewayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\sms\Event\RecipientGatewayEvent;
use Drupal\sms\Event\SmsMessageEvent;
use Drupal\sms\Exception\RecipientRouteException;
use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\Event\SmsEvents;

/**
 * Handles messages before they are processed by queue(), send(), or incoming().
 *
 * Messages queued via queue() are destined for send() or incoming(), they will
 * not be double processed.
 */
class SmsMessageProcessor implements EventSubscriberInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Creates a new SmsMessageProcessor controller.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, ConfigFactoryInterface $config_factory) {
    $this->eventDispatcher = $event_dispatcher;
    $this->configFactory = $config_factory;
  }

  /**
   * Ensures there is at least one recipient on the message.
   *
   * @param \Drupal\sms\Event\SmsMessageEvent $event
   *   The SMS message preprocess event.
   */
  public function ensureRecipients(SmsMessageEvent $event) {
    $sms_messages = $event->getMessages();

    foreach ($sms_messages as $sms_message) {
      $recipients = $sms_message->getRecipients();
      if (!count($recipients)) {
        throw new RecipientRouteException(sprintf('There are no recipients.'));
      }
    }
  }

  /**
   * Ensure all recipients are routed to a gateway.
   *
   * Messages will be split into multiple if recipients need to be routed to
   * different gateways.
   *
   * @param \Drupal\sms\Event\SmsMessageEvent $event
   *   The SMS message preprocess event.
   *
   * @throws \Drupal\sms\Exception\RecipientRouteException
   *   Guarantees a gateway is set on the message, otherwise this exception is
   *   thrown.
   */
  public function ensureGateways(SmsMessageEvent $event) {
    $sms_messages = $event->getMessages();
    $result = [];

    // Ignore messages if they already have a gateway.
    foreach ($sms_messages as $k => $sms_message) {
      if ($sms_message->getGateway() instanceof SmsGatewayInterface) {
        unset($sms_messages[$k]);
        $result[] = $sms_message;
      }
    }

    // Ensure all recipients in this message can be routed to a gateway.
    foreach ($sms_messages as $sms_message) {
      $gateways = [];

      $recipients_all = $sms_message->getRecipients();
      foreach ($recipients_all as $recipient) {
        $gateway = $this->getGatewayForPhoneNumber($recipient);
        if ($gateway instanceof SmsGatewayInterface) {
          $gateways[$gateway->id()][] = $recipient;
        }
        else {
          $event->stopPropagation();
          throw new RecipientRouteException(sprintf('Unable to determine gateway for recipient %s.', $recipient));
        }
      }

      // Recreate SMS messages depending on the gateway.
      $base = $sms_message instanceof EntityInterface ? $sms_message->createDuplicate() : (clone $sms_message);
      $base->removeRecipients($recipients_all);

      foreach ($gateways as $gateway_id => $recipients) {
        $new = $base instanceof EntityInterface ? $base->createDuplicate() : (clone $base);
        $result[] = $new
          ->addRecipients($recipients)
          ->setGateway(SmsGateway::load($gateway_id));
      }
    }

    $event->setMessages($result);
  }

  /**
   * Get a gateway for a phone number.
   *
   * @param string $recipient
   *   A recipient phone number.
   *
   * @return \Drupal\sms\Entity\SmsGatewayInterface|NULL
   *   A gateway for the phone number, or NULL if there is no gateway.
   */
  protected function getGatewayForPhoneNumber($recipient) {
    $event = new RecipientGatewayEvent($recipient);
    /** @var RecipientGatewayEvent $event */
    $event = $this->eventDispatcher
      ->dispatch(SmsEvents::MESSAGE_GATEWAY, $event);

    $gateways = $event->getGatewaysSorted();
    // Use the gateway with the greatest weight.
    $gateway = array_shift($gateways);
    if ($gateway instanceof SmsGatewayInterface) {
      return $gateway;
    }

    // If no gateways found for a phone number, use site fallback default if
    // available.
    $gateway_id = $this->configFactory
      ->get('sms.settings')
      ->get('fallback_gateway');

    return isset($gateway_id) ? SmsGateway::load($gateway_id) : NULL;
  }

  /**
   * Add a delivery report URL to messages.
   *
   * @param \Drupal\sms\Event\SmsMessageEvent $event
   *   The SMS message preprocess event.
   */
  public function deliveryReportUrl(SmsMessageEvent $event) {
    foreach ($event->getMessages() as &$sms_message) {
      if (!$sms_message->getOption('delivery_report_url')) {
        $url = $sms_message->getGateway()->getPushReportUrl();
        try {
          $url = $url->setAbsolute()->toString();
          $sms_message->setOption('delivery_report_url', $url);
        }
        catch (RouteNotFoundException $e) {
        }
      }
    }
  }

  /**
   * Split messages to overcome gateway limits.
   *
   * @param \Drupal\sms\Event\SmsMessageEvent $event
   *   The SMS message preprocess event.
   */
  public function chunkMaxRecipients(SmsMessageEvent $event) {
    $result = [];

    foreach ($event->getMessages() as $sms_message) {
      $max = $sms_message->getGateway()->getMaxRecipientsOutgoing();
      $result = array_merge($result, $sms_message->chunkByRecipients($max));
    }

    $event->setMessages($result);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SmsEvents::MESSAGE_PRE_PROCESS][] = ['ensureRecipients', 1024];
    $events[SmsEvents::MESSAGE_PRE_PROCESS][] = ['ensureGateways', 1024];
    $events[SmsEvents::MESSAGE_PRE_PROCESS][] = ['deliveryReportUrl'];
    $events[SmsEvents::MESSAGE_PRE_PROCESS][] = ['chunkMaxRecipients', -1024];
    return $events;
  }

}
