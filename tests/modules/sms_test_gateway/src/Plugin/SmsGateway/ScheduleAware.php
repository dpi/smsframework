<?php

declare(strict_types=1);

namespace Drupal\sms_test_gateway\Plugin\SmsGateway;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sms\Attribute\SmsGateway;
use Drupal\sms\Entity\SmsMessageInterface as SmsMessageEntityInterface;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResultInterface;

/**
 * Defines a gateway which is aware of scheduled send time.
 */
#[SmsGateway(
  id: self::PLUGIN_ID,
  label: new TranslatableMarkup('Schedule aware gateway'),
  scheduleAware: TRUE,
)]
final class ScheduleAware extends Memory {

  public const PLUGIN_ID = 'memory_schedule_aware';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [];
  }

  public function send(SmsMessageInterface $sms): SmsMessageResultInterface {
    return $sms instanceof SmsMessageEntityInterface
      ? parent::send($sms)
      : throw new \Exception(\sprintf('Not a %s', SmsMessageEntityInterface::class));
  }

}
