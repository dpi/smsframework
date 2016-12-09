<?php

namespace Drupal\sms;

/**
 * Defines an container for SMS objects for processing and a request response.
 */
class SmsProcessingResponse {

  /**
   * An array of messages to process.
   *
   * @var \Drupal\sms\Message\SmsMessageInterface[]
   */
  protected $messages = [];

  /**
   * The response to pass to the request controller.
   *
   * @var mixed
   */
  protected $response;

  /**
   * Get an array of messages to process.
   *
   * @return \Drupal\sms\Message\SmsMessageInterface[]
   *   An array of messages to process.
   */
  public function getMessages() {
    return $this->messages;
  }

  /**
   * Set an array of messages to process.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface[]
   *   An array of messages to process.
   */
  public function setMessages(array $messages) {
    $this->messages = $messages;
  }

  /**
   * Get the response to pass to the request controller.
   *
   * @return mixed
   *   The response to pass to the request controller.
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * Set the response to pass to the request controller.
   *
   * @param mixed $response
   *   Set the response to pass to the request controller.
   */
  public function setResponse($response) {
    $this->response = $response;
  }

}
