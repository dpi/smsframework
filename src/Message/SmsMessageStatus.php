<?php

namespace Drupal\sms\Message;

/**
 * Defines common SMS Framework message state.
 */
class SmsMessageStatus {

  /**
   * Status Unknown.
   *
   * A message would have this to indicate unknown status.
   */
 const UNKNOWN = 0;

  /**
   * Status OK.
   *
   * A message with this status indicates it was successfully sent.
   */
 const OK = 200;

  /**
   * Status DELIVERED.
   *
   * A message with this status indicates it was successfully delivered.
   */
 const DELIVERED = 202;

  /**
   * Status QUEUED.
   *
   * A message with this status indicates it was successfully queued for sending.
   */
 const QUEUED = 302;

  /**
   * Status ERROR.
   *
   * A message with this status indicates it could not be sent (routing reasons).
   */
 const ERROR = 400;

  /**
   * Status NO_CREDIT.
   *
   * A message with this status indicates it could not be sent due to low credit
   * balance.
   */
 const NO_CREDIT = 402;

  /**
   * Status EXPIRED.
   *
   * A message with this status indicates it has expired and has not been sent.
   */
 const EXPIRED = 408;

  /**
   * Status SENT.
   *
   * A message with this status indicates it was successfully sent.
   */
 const SENT = 200;

  /**
   * Status PENDING.
   *
   * A message with this status indicates it is pending delivery.
   */
 const PENDING = 302;

  /**
   * Status NOT_SENT.
   *
   * A message with this status indicates it was not sent.
   */
 const NOT_SENT = 404;

  /**
   * Status NOT_DELIVERED.
   *
   * A message with this status indicates it was sent but not delivered.
   */
 const NOT_DELIVERED = 406;

  /**
   * Status REJECTED.
   *
   * A message with this status indicates it was rejected by the network.
   */
 const REJECTED = 410;

  /**
   * Status INVALID_RECIPIENT.
   *
   * A message with this status indicates the recipient number was invalid.
   */
 const INVALID_RECIPIENT = 412;

  /**
   * Status INVALID_SENDER.
   *
   * A message with this status indicates the sender ID was invalid.
   */
 const INVALID_SENDER = 414;

  /**
   * Status ERROR_ROUTING.
   *
   * A message with this status there was a routing error.
   */
 const ERROR_ROUTING = 420;

}
