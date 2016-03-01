<?php

/**
 * @file
 * Contains \Drupal\sms\Plugin\SmsGatewayPluginInterface
 */

namespace Drupal\sms\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sms\Message\SmsMessageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default implementation of sms gateway plugin
 */
interface SmsGatewayPluginInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface {

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
   * Sends an SMS and invokes the corresponding sms receipt method.
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
   * Parses incoming delivery reports and returns the DeliveryReport objects.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object containing the delivery report in raw format.
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   A HTTP response that will be sent back to the SMSC. The plugin can alter
   *   the content of the response by @code$response->setContent()@endcode.
   *
   * @return \Drupal\sms\Message\SmsDeliveryReportInterface[]
   *   An array of the delivery reports which have been received.
   */
  public function parseDeliveryReports(Request $request, Response $response);

  /**
   * Pulls delivery reports from the SMS gateway server.
   *
   * @param string[]|null $message_ids
   *   The list of specific message_ids to poll. NULL to pull all reports.
   *
   * @return \Drupal\sms\Message\SmsDeliveryReportInterface[]
   *   An array of the delivery reports which have been pulled.
   */
  public function pullDeliveryReports(array $message_ids = NULL);

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
  public function validateNumbers(array $numbers, array $options = []);

  /**
   * Gets the path for the delivery reports for this gateway instance.
   *
   * @param bool $absolute
   *   Whether to return a relative or absolute path.
   *
   * @return string
   *   The absolute delivery report url if $absolute is true (default); returns
   *   the domain-relative path if $absolute is false.
   */
  public function getDeliveryReportPath($absolute = TRUE);

  /**
   * Sets the machine name of the config entity that owns this plugin instance.
   *
   * @param string $machine_name
   *   The machine name of the config entity.
   */
  public function setGatewayName($machine_name);

}
