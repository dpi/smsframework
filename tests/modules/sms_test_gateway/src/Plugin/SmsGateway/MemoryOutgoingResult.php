<?php

namespace Drupal\sms_test_gateway\Plugin\SmsGateway;

use Drupal\sms\Message\SmsMessageInterface;

/**
 * Defines a gateway with defective return values for its' send method.
 *
 * @SmsGateway(
 *   id = "memory_outgoing_result",
 *   label = @Translation("Memory Outgoing Result"),
 *   outgoing_message_max_recipients = -1
 * )
 */
class MemoryOutgoingResult extends Memory {

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms_message) {
    $result = parent::send($sms_message);

    if (\Drupal::state()->get('sms_test_gateway.memory_outgoing_result.missing_result')) {
      return NULL;
    }

    $delete_reports = \Drupal::state()->get('sms_test_gateway.memory_outgoing_result.delete_reports');
    if ($delete_reports > 0) {
      $reports = $result->getReports();

      if (!count($reports)) {
        throw new \Exception('There are no reports to delete.');
      }

      // Slice off the first {$delete_reports}x reports.
      $reports = array_slice($reports, $delete_reports);

      $result->setReports($reports);
      return $result;
    }

    return $result;
  }

}
