<?php

/**
 * @file
 * Contains \Drupal\sms\Gateway\GatewayInterface
 */

namespace Drupal\sms\Gateway;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\sms\Message\SmsMessageInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default implementation of sms gateway plugin
 */
interface GatewayInterface extends ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Status Unknown.
   *
   * A gateway should return this status to indicate unknown status.
   */
  const STATUS_UNKNOWN = 0;

  /**
   * Status OK.
   *
   * A gateway should return this status to indicate message successfully sent.
   */
  const STATUS_OK = 200;

  /**
   * Authentication error.
   *
   * A gateway should return this status to indicate authentication error.
   */
  const STATUS_ERR_AUTH = 401;

  /**
   * Invalid Call.
   *
   * A gateway should return this status to indicate invalid call attempt.
   */
  const STATUS_ERR_INVALID_CALL = 400;

  /**
   * Gateway endpoint not found.
   *
   * A gateway should return this status to indicate gateway endpoint not found.
   */
  const STATUS_ERR_NOT_FOUND = 404;

  /**
   * Message limits exceeded.
   *
   * A gateway should return this status to indicate message limits exceeded.
   */
  const STATUS_ERR_MSG_LIMITS = 413;

  /**
   * Routing error.
   *
   * A gateway should return this status to indicate message routing error.
   */
  const STATUS_ERR_MSG_ROUTING = 502;

  /**
   * Message queuing error.
   *
   * A gateway should return this status to indicate message queuing error.
   */
  const STATUS_ERR_MSG_QUEUING = 408;

  /**
   * Other message error.
   *
   * A gateway should return this status to indicate other message error.
   */
  const STATUS_ERR_MSG_OTHER = 409;

  /**
   * Source number or id error.
   *
   * A gateway should return this status to indicate sender number or id error.
   */
  const STATUS_ERR_SRC_NUMBER = 415;

  /**
   * Destination number error.
   *
   * A gateway should return this status to indicate destination number error.
   */
  const STATUS_ERR_DEST_NUMBER = 416;

  /**
   * Credit error.
   *
   * A gateway should return this status to indicate insufficient credit error.
   */
  const STATUS_ERR_CREDIT = 402;

  /**
   * Other error.
   *
   * A gateway should return this status to indicate a known error condition but
   * not mappable to any equivalent above.
   */
  const STATUS_ERR_OTHER = 500;

  /**
   * Sends an sms and invokes the corresponding sms receipt method.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms
   *   The sms to be sent.
   * @param array $options
   *   Options to be applied while processing this sms.
   *
   * @return \Drupal\sms\Message\SmsMessageResultInterface
   *   The result of the sms messaging operation.
   */
  public function send(SmsMessageInterface $sms, array $options);

  /**
   * Returns the credit balance available on this gateway.
   *
   * @return number
   */
  public function balance();

  /**
   * Handles delivery reports and invokes the corresponding sms_receipt method.
   *
   * @param \Symfony\Component\HttpFoundation\Request
   *   Request object containing the delivery report in raw format.
   */
  public function deliveryReport(Request $request);

  /**
   * Gets the last error message from the gateway.
   *
   * @return array
   *   An array of values containing the following:
   *   - code: the error code.
   *   - message: the error message.
   */
  public function getError();

  /**
   * Gets the machine  name of the gateway.
   *
   * @return string
   */
  public function getIdentifier();

  /**
   * Gets the readable name of the gateway.
   *
   * @return string
   */
  public function getName();

  /**
   * Gets the user-readable translated name of the gateway.
   *
   * @return string
   */
  public function getLabel();

  /**
   * Returns a boolean to show if the gateway is configurable.
   *
   * @return bool
   *   TRUE if the form is configurable, FALSE if not.
   */
  public function isConfigurable();

  /**
   * Returns a form to be appended to the send form.
   *
   * @param array $form
   *   The parent form array.
   * @param array $form_state
   *   Form state.
   * @returns array
   *   The form for additional gateway-specific sending options.
   */
  public function sendForm(array &$form, FormStateInterface $form_state);

  /**
   * Gets the enabled status of the gateway.
   *
   * @return bool
   */
  public function isEnabled();

  /**
   * Sets the enabled status of the gateway.
   *
   * @param bool $status
   *   The enabled status.
   */
  public function setEnabled($status = TRUE);

  /**
   * Gets the plugin-specific configuration for this gateway.
   */
  public function getCustomConfiguration();

  /**
   * Sets the plugin-specific configuration for this gateway.
   */
  public function setCustomConfiguration($configuration);

  /**
   * Carry out gateway-specific number validation.
   *
   * @param array $numbers
   *   The list of phone numbers to be validated.
   * @param array $options
   *   Options to be considered for validation.
   *
   * @return array
   *   An array containing an error message for each validation failure.
   */
  public function validateNumbers(array $numbers, array $options = array());

}
