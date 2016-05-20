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
function hook_sms_delivery_report(array $reports, Response $response) {
  $response->setContent('OK');
}
