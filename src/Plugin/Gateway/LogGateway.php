<?php
/**
 * @file
 * Contains \Drupal\sms\Plugin\Gateway\LogGateway
 */

namespace Drupal\sms\Plugin\Gateway;

use Drupal\sms\Gateway\SmsGatewayPluginBase;
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
