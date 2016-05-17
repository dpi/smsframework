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
   * Process and chunk a SMS message before it is queued, sent, or received.
   *
   * @param \Drupal\sms\Event\SmsMessageEvent $event
   *   The SmsMessageEvent event.
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
   * Process and chunk a SMS message after it is queued, sent, or received.
   *
   * @param \Drupal\sms\Event\SmsMessageEvent $event
   *   The SmsMessageEvent event.
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
   * Determines valid gateways for a recipient phone number.
   *
   * This event is not always dispatched. It is only dispatch if no other
   * preprocessors have added a gateway to a message.
   *
   * If you don't know whether you should add a gateway for a recipient, then
   * it is best to not do anything at all. Let the rest of the framework
   * continue to try to find a gateway.
   *
   * Only one gateway will be applied to the message for this recipient. The
   * gateway with the largest priority wins.
   *
   * @param \Drupal\sms\Event\RecipientGatewayEvent $event
   *   The RecipientGatewayEvent event.
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
    $events['sms.message.preprocess'][] = ['mySmsMessagePreprocess'];
    $events['sms.message.postprocess'][] = ['mySmsMessagePostprocess'];
    $events['sms.message.gateway'][] = ['mySmsMessageGateway'];
    return $events;
  }

}

