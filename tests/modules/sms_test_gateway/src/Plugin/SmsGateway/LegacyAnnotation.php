<?php

declare(strict_types=1);

namespace Drupal\sms_test_gateway\Plugin\SmsGateway;

use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResultInterface;
use Drupal\sms\Plugin\SmsGatewayPluginBase;

/**
 * @SmsGateway(
 *   id = \Drupal\sms_test_gateway\Plugin\SmsGateway\LegacyAnnotation::PLUGIN_ID,
 *   label = @Translation("Legacy annotation"),
 *   incoming = TRUE,
 *   outgoing_message_max_recipients = 2,
 *   incoming = TRUE,
 *   incoming_route = TRUE,
 *   schedule_aware = TRUE,
 *   reports_pull = TRUE,
 *   reports_push = TRUE,
 *   credit_balance_available = TRUE,
 * )
 */
final class LegacyAnnotation extends SmsGatewayPluginBase {

  public const PLUGIN_ID = 'legacy_annotation';

  public function send(SmsMessageInterface $sms): SmsMessageResultInterface {
    throw new \LogicException('No-op');
  }

}
