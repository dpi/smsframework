<?php

namespace Drupal\sms_test\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\Event\RecipientGatewayEvent;

/**
 * Test event subscriber.
 */
class SmsTestEventSubscriber implements EventSubscriberInterface {

  /**
   * Adds a gateway with ID 'test_gateway_200', with weight '200'.
   *
   * @param \Drupal\sms\Event\RecipientGatewayEvent $event
   *   The RecipientGatewayEvent event.
   */
  public function testAddGateway200(RecipientGatewayEvent $event) {
    if (\Drupal::state()->get('sms_test_event_subscriber__test_gateway_200', FALSE)) {
      $gateway = SmsGateway::load('test_gateway_200');
      $event->addGateway($gateway, 200);
    }
  }

  /**
   * Adds a gateway with ID 'test_gateway_400', with weight '400'.
   *
   * @param \Drupal\sms\Event\RecipientGatewayEvent $event
   *   The RecipientGatewayEvent event.
   */
  public function testAddGateway400(RecipientGatewayEvent $event) {
    if (\Drupal::state()->get('sms_test_event_subscriber__test_gateway_400', FALSE)) {
      $gateway = SmsGateway::load('test_gateway_400');
      $event->addGateway($gateway, 400);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['sms.message.gateway'][] = ['testAddGateway200'];
    $events['sms.message.gateway'][] = ['testAddGateway400'];
    return $events;
  }

}
