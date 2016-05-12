<?php

namespace Drupal\sms\EventSubscriber;

use Drupal\Core\Url;
use Drupal\sms\Entity\SmsGatewayInterface;
use Drupal\sms\Event\RecipientGatewayEvent;
use Drupal\sms\Event\SmsMessageEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\sms\Exception\RecipientRouteException;
use Drupal\sms\Entity\SmsGateway;

/**
 * Handles messages before they are processed by queue(), send(), or incoming().
 *
 * Messages queued via queue() are destined for send() or incoming(), they will
 * not be double processed.
 */
class SmsMessageProcessor implements EventSubscriberInterface {

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
        unset($k);
        $result[] = $sms_message;
      }
    }

    // Ensure all recipients in this message can be routed to a gateway.
    foreach ($sms_messages as $sms_message) {
      $gateways = [];

      foreach ($sms_message->getRecipients() as $recipient) {
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
      $base = $sms_message->createDuplicate();
      $base->set('recipient_phone_number', []);

      foreach ($gateways as $gateway_id => $recipients) {
        $result[] = $base->createDuplicate()
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
   * @return \Drupal\Core\Entity\EntityInterface|NULL
   */
  protected function getGatewayForPhoneNumber($recipient) {
    // @todo inject
    $eventDispatcher = \Drupal::service('event_dispatcher');

    $event = new RecipientGatewayEvent($recipient);
    /** @var RecipientGatewayEvent $event */
    $event = $eventDispatcher->dispatch('sms.message.gateway', $event);

    $gateways = $event->getGatewaysSorted();
    // Use the gateway with the greatest weight.
    $gateway = array_shift($gateways);
    if ($gateway instanceof SmsGatewayInterface) {
      return $gateway;
    }

    // If no gateways found for a phone number, use site fallback default if
    // available.
    $gateway_id = \Drupal::config('sms.settings')
      ->get('default_gateway');

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
        $route_parameters = ['sms_gateway' => $sms_message->getGateway()->id()];
        $url = Url::fromRoute('sms.process_delivery_report', $route_parameters);
        $sms_message->setOption('delivery_report_url', $url->setAbsolute()->toString());
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
   * This is just a POC, wouldnt be included in core.
   *
   * @param \Drupal\sms\Event\SmsMessageEvent $event
   */
  public function almaudohTest(SmsMessageEvent $event) {
    $sms_messages = $event->getMessages();

    // Create a identifier so we are creating a meta relationship between the
    // chunked messages.
    $generator = new \Drupal\Component\Uuid\Php();
    $relationship_uuid = $generator->generate();

    $total_recipients = 0;
    $total_messages = count($sms_messages);
    foreach ($sms_messages as &$sms_message) {
      $total_recipients += count($sms_message->getRecipients());
      $sms_message->setOption('almaudoh_foobar', $relationship_uuid);
    }

    \Drupal::logger(__FUNCTION__)->info('This message has a total of @total_recipients recipients, and was chunked into @total_messages', [
      '@total_recipients' => $total_recipients,
      '@total_messages' => $total_messages,
    ]);
  }

  public function testAddGateway(RecipientGatewayEvent $event) {
//    $event->addGateway(SmsGateway::load('log'));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['sms.message.preprocess'][] = ['ensureGateways', 1024];
    $events['sms.message.preprocess'][] = ['deliveryReportUrl'];
    $events['sms.message.preprocess'][] = ['chunkMaxRecipients', -1024];
    // Do it last.
    $events['sms.message.preprocess'][] = ['almaudohTest', -9999];
    $events['sms.message.gateway'][] = ['testAddGateway'];
    return $events;
  }

}
