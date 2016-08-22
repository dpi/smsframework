<?php

/**
 * @file
 * SMS Framework hooks.
 */

/**
 * Alter gateway plugin definitions.
 *
 * This hook gives you a chance to modify gateways after all plugin definitions
 * are discovered.
 *
 * @param array $gateways
 *   An array of gateway definitions keyed by plugin ID.
 */
function hook_sms_gateway_info_alter(&$gateways) {
  $gateways['log']['label'] = new \Drupal\Core\StringTranslation\TranslatableMarkup('The Logger');
}

/**
 * Called before the SMS message is processed by the gateway plugin.
 *
 * @param \Drupal\sms\Message\SmsMessageInterface $sms_message
 *   A SMS message.
 */
function hook_sms_incoming_preprocess(\Drupal\sms\Message\SmsMessageInterface $sms_message) {
}

/**
 * Called after the SMS message is processed by the gateway plugin.
 *
 * @param \Drupal\sms\Message\SmsMessageInterface $sms_message
 *   A SMS message.
 */
function hook_sms_incoming_postprocess(\Drupal\sms\Message\SmsMessageInterface $sms_message) {
}

/**
 * Called after SMS delivery reports are processed by the SMS provider.
 *
 * This hook allows gateways to customize responses that would be returned to
 * the gateway server.
 *
 * @param \Drupal\sms\Message\SmsDeliveryReportInterface[] $reports
 *   Delivery reports received from the SMS gateway.
 * @param \Symfony\Component\HttpFoundation\Response $response
 *   The HTTP response that will be sent back to the server.
 */
function hook_sms_delivery_report(array $reports, \Symfony\Component\HttpFoundation\Response $response) {
  $response->setContent('OK');
}

/**
 * Event subscribers for SMS Framework.
 *
 * Service definition:
 * <code>
 * ```yaml
 *  my_module.my_event_subscriber:
 *    class: Drupal\my_module\EventSubscriber\MySmsEventSubscriber
 *    tags:
 *     - { name: event_subscriber }
 * ```
 * </code>
 *
 * <code>
 * <?php
 * namespace Drupal\my_module\EventSubscriber;
 * ?>
 * </code>
 */
class MySmsEventSubscriber implements \Symfony\Component\EventDispatcher\EventSubscriberInterface {

  /**
   * @see \Drupal\sms\Event\SmsEvents::MESSAGE_PRE_PROCESS
   */
  public function mySmsMessagePreprocess(\Drupal\sms\Event\SmsMessageEvent $event) {
    $result = [];
    foreach ($event->getMessages() as $message) {
      // Modify or chunk messages.
      $result[] = $message;
    }
    $event->setMessages($result);
  }

  /**
   * @see \Drupal\sms\Event\SmsEvents::MESSAGE_POST_PROCESS
   */
  public function mySmsMessagePostProcess(\Drupal\sms\Event\SmsMessageEvent $event) {
    $result = [];
    foreach ($event->getMessages() as $message) {
      // Modify or chunk messages.
      $result[] = $message;
    }
    $event->setMessages($result);
  }

  /**
   * @see \Drupal\sms\Event\SmsEvents::MESSAGE_GATEWAY
   */
  public function mySmsMessageGateway(\Drupal\sms\Event\RecipientGatewayEvent $event) {
    // The recipient phone number.
    $event->getRecipient();
    // Add a gateway for a phone number.
    $event->addGateway($a_gateway);
    // Add a gateway with a priority.
    $event->addGateway($a_gateway, 333);
    $event->addGateway($a_gateway, -333);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[\Drupal\sms\Event\SmsEvents::MESSAGE_PRE_PROCESS][] = ['mySmsMessagePreprocess'];
    $events[\Drupal\sms\Event\SmsEvents::MESSAGE_POST_PROCESS][] = ['mySmsMessagePostprocess'];
    $events[\Drupal\sms\Event\SmsEvents::MESSAGE_GATEWAY][] = ['mySmsMessageGateway'];
    return $events;
  }

}
