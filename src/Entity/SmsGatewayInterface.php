<?php

namespace Drupal\sms\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a SMS Gateway entity.
 */
interface SmsGatewayInterface extends ConfigEntityInterface {

  /**
   * Returns the plugin instance.
   *
   * @return \Drupal\sms\Plugin\SmsGatewayPluginInterface
   *   The plugin instance for this SMS Gateway.
   */
  public function getPlugin();

  /**
   * Returns the plugin ID.
   *
   * @return string
   *   The plugin ID for this SMS Gateway.
   */
  public function getPluginId();

  /**
   * Get whether messages sent to this gateway should be sent immediately.
   *
   * @return boolean
   *   Whether messages sent to this gateway should be sent immediately.
   */
  public function getSkipQueue();

  /**
   * Set whether messages sent to this gateway should be sent immediately.
   *
   * @param bool $skip_queue
   *   Whether messages sent to this gateway should be sent immediately.
   *
   * @return $this
   *   Return this gateway for chaining.
   */
  public function setSkipQueue($skip_queue);

  /**
   * Get how many seconds to hold messages.
   *
   * @param int $direction
   *   The direction of the message. See SmsMessageInterface::DIRECTION_*
   *   constants.
   *
   * @return int
   *   How long messages should persist in seconds. -1 to never expire.
   */
  public function getRetentionDuration($direction);

  /**
   * Set how many seconds to hold messages..
   *
   * @param int $direction
   *   The direction of the message. See SmsMessageInterface::DIRECTION_*
   *   constants.
   * @param int $retention_duration
   *   How many seconds to hold messages, or use -1 to never expire.
   *
   * @return $this
   *   Return this gateway for chaining.
   */
  public function setRetentionDuration($direction, $retention_duration);

  /**
   * Get maximum number of recipients per outgoing message.
   *
   * @return int
   *   Maximum number of recipients, or -1 for no limit.
   */
  public function getMaxRecipientsOutgoing();

  /**
   * Get whether this gateway is schedule aware.
   *
   * @return boolean
   *   Whether this gateway is schedule aware.
   */
  public function isScheduleAware();

  /**
   * Gets whether this gateway can pull reports.
   *
   * @return boolean
   *   Whether this gateway can pull reports.
   *
   * @see \Drupal\sms\Annotation\SmsGateway::reports_pull
   */
  public function supportsReportsPull();

  /**
   * Gets whether this gateway can handle reports pushed to the site.
   *
   * @return boolean
   *   Whether this gateway can handle reports pushed to the site.
   *
   * @see \Drupal\sms\Annotation\SmsGateway::reports_push
   */
  public function supportsReportsPush();

  /**
   * Get whether this gateway supports credit balance queries.
   *
   * @return boolean
   *   Whether this gateway supports credit balance queries.
   *
   * @see \Drupal\sms\Annotation\SmsGateway::credit_balance_available
   */
  public function supportsCreditBalanceQuery();

}
