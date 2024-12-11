<?php

declare(strict_types=1);

namespace Drupal\sms_test_gateway\Plugin\SmsGateway;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sms\Attribute\SmsGateway;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResultInterface;

/**
 * Defines a gateway with defective return values for its' send method.
 */
#[SmsGateway(
  id: self::PLUGIN_ID,
  label: new TranslatableMarkup('Memory Outgoing Result'),
  outgoingMessageMaxRecipients: -1,
)]
final class MemoryOutgoingResult extends Memory {
  public const PLUGIN_ID = 'memory_outgoing_result';

  public function send(SmsMessageInterface $sms): SmsMessageResultInterface {
    $result = parent::send($sms);

    $delete_reports = static::state()->get('sms_test_gateway.memory_outgoing_result.delete_reports');
    if ($delete_reports > 0) {
      $reports = $result->getReports();

      if ($reports === []) {
        throw new \Exception('There are no reports to delete.');
      }

      // Slice off the first {$delete_reports}x reports.
      $reports = \array_slice($reports, $delete_reports);

      $result->setReports($reports);
      return $result;
    }

    return $result;
  }

}
