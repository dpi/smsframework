<?php
/**
 * @file
 * Contains \Drupal\sms\Plugin\Gateway\LogGateway
 */

namespace Drupal\sms\Plugin\Gateway;

use Drupal\sms\Gateway\GatewayBase;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;

/**
 * @SmsGateway(
 *   id = "log",
 *   label = @Translation("Log only"),
 *   configurable = false,
 * )
 */
class LogGateway extends GatewayBase {

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms, array $options) {
    // Log sms message to drupal logger.
    $this->logger()->notice('SMS message sent to %number with the text: @message',
      ['%number' => implode(', ', $sms->getRecipients()), '@message' => $sms->getMessage()]);
    return new SmsMessageResult(['status' => TRUE]);
  }

}
