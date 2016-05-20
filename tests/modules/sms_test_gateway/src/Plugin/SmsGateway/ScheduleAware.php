<?php

namespace Drupal\sms_test_gateway\Plugin\SmsGateway;

use Drupal\sms\Entity\SmsMessageInterface as SmsMessageEntityInterface;
use Drupal\sms\Message\SmsMessageInterface;

/**
 * Defines a gateway which is aware of scheduled send time.
 *
 * @SmsGateway(
 *   id = "memory_schedule_aware",
 *   label = @Translation("Schedule aware gateway"),
 *   schedule_aware = TRUE,
 * )
 */
class ScheduleAware extends Memory {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms) {
    if ($sms instanceof SmsMessageEntityInterface) {
      return parent::send($sms);
    }
    else {
      throw new \Exception('Not a SMS message entity');
    }
  }

}
