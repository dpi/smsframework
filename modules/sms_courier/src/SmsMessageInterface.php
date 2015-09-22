<?php

/**
 * @file
 * Contains \Drupal\sms_courier\SmsMessageInterface.
 */

namespace Drupal\sms_courier;

use Drupal\courier\ChannelInterface;
use Drupal\sms\Message\SmsMessageInterface as CoreSmsMessageInterface;

interface SmsMessageInterface extends CoreSmsMessageInterface, ChannelInterface {

  public function getRecipient();
  // @todo these should be on \Drupal\sms\Message\SmsMessageInterface
  public function setRecipient($phone_number);
  public function setMessage($message);


}
