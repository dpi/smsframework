<?php

/**
 * @file
 * Contains \Drupal\sms\Plugin\SmsGateway\LogGateway
 */

namespace Drupal\sms\Plugin\SmsGateway;

use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsDeliveryReportInterface;
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
    // Log sms message to drupal logger.
    $this->logger()->notice('SMS message sent to %number with the text: @message',
      ['%number' => implode(', ', $sms->getRecipients()), '@message' => $sms->getMessage()]);
    $return = ['status' => TRUE];
    $return['reports'] = [];
    foreach ($sms->getRecipients() as $number) {
      $return['reports'][$number] = new SmsDeliveryReport([
        'status' => SmsDeliveryReportInterface::STATUS_DELIVERED,
        'recipient' => $number,
        'gateway_status' => 'DELIVERED',
      ]);
    }
    return new SmsMessageResult($return);
  }

}
