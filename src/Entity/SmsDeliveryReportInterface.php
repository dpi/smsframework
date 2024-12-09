<?php

declare(strict_types=1);

namespace Drupal\sms\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\sms\Message\SmsDeliveryReportInterface as PlainDeliveryReportInterface;

/**
 * Interface for SMS delivery report entity.
 */
interface SmsDeliveryReportInterface extends PlainDeliveryReportInterface, ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the parent SMS message entity.
   */
  public function getSmsMessage(): SmsMessageInterface;

  /**
   * Sets the parent SMS message entity.
   *
   * @return $this
   */
  public function setSmsMessage(SmsMessageInterface $sms_message);

}
