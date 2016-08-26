<?php

namespace Drupal\sms\Message;

/**
 * Defines common SMS Framework message state.
 */
class SmsMessageStatus {

  /**
   * Message was queued for sending.
   */
  const QUEUED = 'queued';

  /**
   * Message was successfully delivered to the recipient.
   */
  const DELIVERED = 'delivered';

  /**
   * Message could not be processed due to an unknown problem with the gateway.
   */
  const ERROR = 'error';

  /**
   * Message could not be processed due to low credit.
   */
  const NO_CREDIT = 'no_credit';

  /**
   * Message expired and was not sent.
   */
  const EXPIRED = 'expired';

  /**
   * Message is pending delivery.
   */
  const PENDING = 'pending';

  /**
   * Message was rejected by the gateway.
   */
  const REJECTED = 'rejected';

  /**
   * Indicates a recipient of the message is invalid.
   */
  const INVALID_RECIPIENT = 'invalid_recipient';

  /**
   * Indicates the sender ID is invalid.
   */
  const INVALID_SENDER = 'invalid_sender';

  /**
   * Failed to authenticate with gateway.
   */
  const AUTHENTICATION = 'authentication';

  /**
   * Too many requests.
   */
  const EXCESSIVE_REQUESTS = 'flooded';

  /**
   * Content of message invalid or not supported by gateway.
   */
  const CONTENT_INVALID = 'content_invalid';

}
