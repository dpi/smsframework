<?php

/**
 * @file
 * Contains \Drupal\sms\Plugin\SmsGateway\LogGateway
 */

namespace Drupal\sms\Plugin\SmsGateway;

use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;

/**
 * @SmsGateway(
 *   id = "log",
 *   label = @Translation("Drupal log"),
 *   outgoing_message_max_recipients = -1,
 * )
 */
class LogGateway extends SmsGatewayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms) {
    $this->logger()->notice('SMS message sent to %number with the text: @message',
      ['%number' => implode(', ', $sms->getRecipients()), '@message' => $sms->getMessage()]);

    $result = (new SmsMessageResult());
    $reports = [];
    foreach ($sms->getRecipients() as $number) {
      $reports[] = (new SmsDeliveryReport())
        ->setRecipient($number)
        ->setStatus(SmsMessageReportStatus::DELIVERED)
        ->setStatusMessage('DELIVERED')
        ->setTimeDelivered(REQUEST_TIME);
    }
    $result->setReports($reports);

    return $result;
  }

}
