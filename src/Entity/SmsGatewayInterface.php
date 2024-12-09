<?php

declare(strict_types=1);

namespace Drupal\sms\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Url;
use Drupal\sms\Plugin\SmsGatewayPluginInterface;

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
  public function getPlugin(): SmsGatewayPluginInterface;

  /**
   * Returns the plugin ID.
   *
   * @return string
   *   The plugin ID for this SMS Gateway.
   */
  public function getPluginId(): string;

  /**
   * Get whether messages sent to this gateway should be sent immediately.
   *
   * @return bool
   *   Whether messages sent to this gateway should be sent immediately.
   */
  public function getSkipQueue(): bool;

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
   * Get the internal path where incoming messages are received.
   *
   * @return string|null
   *   The internal path where incoming messages are received.
   */
  public function getPushIncomingPath(): ?string;

  /**
   * Set the internal path where incoming messages are received.
   *
   * @param string|null $path
   *   The internal path where incoming messages are received.
   *
   * @return $this
   *   Return this gateway for chaining.
   */
  public function setPushIncomingPath($path);

  /**
   * Get the url where pushed delivery reports can be received.
   *
   * @return \Drupal\Core\Url
   *   The url where pushed delivery reports can be received.
   */
  public function getPushReportUrl(): Url;

  /**
   * Get the internal path where pushed delivery reports can be received.
   *
   * @return string|null
   *   The internal path where pushed delivery reports can be received.
   */
  public function getPushReportPath(): ?string;

  /**
   * Set the internal path where pushed delivery reports can be received.
   *
   * @param string|null $path
   *   The internal path where pushed delivery reports can be received.
   *
   * @return $this
   *   Return this gateway for chaining.
   */
  public function setPushReportPath($path);

  /**
   * Get how many seconds to hold messages.
   *
   * @phpstan-param \Drupal\sms\Direction::* $direction
   *    The direction of the message.
   *
   * @return int<-1, max>
   *   How long messages should persist in seconds. -1 to never expire.
   */
  public function getRetentionDuration(int $direction): int;

  /**
   * Set how many seconds to hold messages.
   *
   * @param \Drupal\sms\Direction::* $direction
   *   The direction of the message. See SmsMessageInterface::DIRECTION_*
   *   constants.
   * @param int $retention_duration
   *   How many seconds to hold messages, or use -1 to never expire.
   *
   * @return $this
   *   Return this gateway for chaining.
   */
  public function setRetentionDuration(int $direction, $retention_duration);

  /**
   * Get maximum number of recipients per outgoing message.
   *
   * @return int
   *   Maximum number of recipients, or -1 for no limit.
   */
  public function getMaxRecipientsOutgoing(): int;

  /**
   * Whether the gateway supports receiving messages.
   *
   * @return bool
   *   Whether the gateway supports receiving messages.
   */
  public function supportsIncoming(): bool;

  /**
   * Whether to automatically create a route for receiving incoming messages.
   *
   * @return bool
   *   Whether to automatically create a route for receiving incoming messages.
   */
  public function autoCreateIncomingRoute(): bool;

  /**
   * Get whether this gateway is schedule aware.
   *
   * @return bool
   *   Whether this gateway is schedule aware.
   */
  public function isScheduleAware(): bool;

  /**
   * Gets whether this gateway can pull reports.
   *
   * @return bool
   *   Whether this gateway can pull reports.
   *
   * @see \Drupal\sms\Annotation\SmsGateway::reports_pull
   */
  public function supportsReportsPull(): bool;

  /**
   * Gets whether this gateway can handle reports pushed to the site.
   *
   * @return bool
   *   Whether this gateway can handle reports pushed to the site.
   *
   * @see \Drupal\sms\Annotation\SmsGateway::reports_push
   */
  public function supportsReportsPush(): bool;

  /**
   * Get whether this gateway supports credit balance queries.
   *
   * @return bool
   *   Whether this gateway supports credit balance queries.
   *
   * @see \Drupal\sms\Annotation\SmsGateway::credit_balance_available
   */
  public function supportsCreditBalanceQuery(): bool;

}
