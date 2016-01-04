<?php

/**
 * @file
 * Contains \Drupal\sms\Plugin\SmsGateway\LogGateway
 */

namespace Drupal\sms\Plugin\SmsGateway;

use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;

/**
 * @SmsGateway(
 *   id = "log",
 *   label = @Translation("Drupal log"),
 * )
 */
class LogGateway extends SmsGatewayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms, array $options) {
    // Log sms message to drupal logger.
    $this->logger()->notice('SMS message sent to %number with the text: @message',
      ['%number' => implode(', ', $sms->getRecipients()), '@message' => $sms->getMessage()]);
    $return = ['status' => TRUE];
    $return['report'] = array_fill_keys($sms->getRecipients(), ['status' => TRUE, 'message_id' => rand(1, 999999)]);
    return new SmsMessageResult($return);
  }

}
