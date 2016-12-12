<?php

namespace Drupal\sms_test_gateway\Plugin\SmsGateway;

use Symfony\Component\HttpFoundation\Response;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms\SmsProcessingResponse;
/**
 *
 * Defines a gateway supporting incoming route.
 *
 * @SmsGateway(
 *   id = "incoming",
 *   label = @Translation("Incoming"),
 *   incoming = TRUE,
 *   incoming_route = TRUE,
 * )
 */
class Incoming extends SmsGatewayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms) {
  }

  /**
   * Process an incoming message request.
   *
   * @return \Drupal\sms\SmsProcessingResponse
   *   A SMS processing response task.
   */
  function processIncoming() {
    $task = new SmsProcessingResponse();
    $response = new Response('All good', 200);
    $task->setResponse($response);

    return $task;
  }

}
