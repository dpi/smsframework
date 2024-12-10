<?php

declare(strict_types=1);

namespace Drupal\sms\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\sms\Message\SmsMessageResultInterface as PlainMessageResultInterface;

/**
 * Interface for SMS message result entity.
 */
interface SmsMessageResultInterface extends PlainMessageResultInterface, ContentEntityInterface {

  /**
   * Gets the parent SMS message entity.
   */
  public function getSmsMessage(): ?SmsMessageInterface;

  /**
   * Sets the parent SMS message entity.
   *
   * @return $this
   */
  public function setSmsMessage(SmsMessageInterface $sms_message);

}
