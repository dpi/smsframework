<?php

namespace Drupal\sms\Event;

/**
 * Defines SMS Framework events.
 */
final class SmsEvents {

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
   * Only one gateway will be applied to the message for the recipient. The
   * gateway with the largest priority wins.
   *
   * @Event
   *
   * @see \Drupal\sms\Event\RecipientGatewayEvent
   */
  const MESSAGE_GATEWAY = 'sms.message.gateway';

  /**
   * Process and chunk a SMS message before it is queued, sent, or received.
   *
   * @Event
   *
   * @see \Drupal\sms\Event\SmsMessageEvent
   */
  const MESSAGE_PRE_PROCESS = 'sms.message.pre_process';

  /**
   * Process and chunk a SMS message after it is queued, sent, or received.
   *
   * @Event
   *
   * @see \Drupal\sms\Event\SmsMessageEvent
   */
  const MESSAGE_POST_PROCESS = 'sms.message.post_process';

}
