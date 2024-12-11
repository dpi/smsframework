<?php

declare(strict_types=1);

namespace Drupal\sms_test_gateway\Plugin\SmsGateway;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sms\Attribute\SmsGateway;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResultInterface;
use Drupal\sms\Plugin\SmsGatewayPluginBase;

/**
 * Defines a gateway which does not implement incoming messages.
 */
#[SmsGateway(
  id: self::PLUGIN_ID,
  label: new TranslatableMarkup('No Incoming'),
)]
final class NoIncoming extends SmsGatewayPluginBase {

  public const PLUGIN_ID = 'memory_noincoming';

  public function send(SmsMessageInterface $sms): SmsMessageResultInterface {
    throw new \LogicException('No-op');
  }

}
