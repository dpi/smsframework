<?php

declare(strict_types=1);

namespace Drupal\sms_test_gateway\Plugin\SmsGateway;

use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sms\Attribute\SmsGateway;
use Drupal\sms\Direction;
use Drupal\sms\Entity\SmsGatewayInterface;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessage;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageResultInterface;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms\SmsProcessingResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines a gateway supporting incoming route.
 *
 * @phpstan-type JsonRequestItem array{recipients?: string[], message?: string, sender_number?: string}
 */
#[SmsGateway(
  id: self::PLUGIN_ID,
  label: new TranslatableMarkup('Incoming'),
  incoming: TRUE,
  incomingRoute: TRUE,
)]
final class Incoming extends SmsGatewayPluginBase {

  public const PLUGIN_ID = 'incoming';

  public function send(SmsMessageInterface $sms): SmsMessageResultInterface {
    throw new \LogicException('Not implemented');
  }

  /**
   * Process an incoming message POST request.
   *
   * This callback expects a 'messages' POST value containing JSON.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway
   *   The gateway instance.
   *
   * @return \Drupal\sms\SmsProcessingResponse
   *   A SMS processing response task.
   */
  public function processIncoming(Request $request, SmsGatewayInterface $sms_gateway): SmsProcessingResponse {
    /** @var array{messages: JsonRequestItem[]} $json */
    $json = Json::decode($request->getContent());

    $messages = [];
    foreach ($json['messages'] as $raw_message) {
      $result = new SmsMessageResult();

      foreach ($raw_message['recipients'] ?? [] as $recipient) {
        $report = (new SmsDeliveryReport())
          ->setRecipient($recipient);
        $result->addReport($report);
      }

      $message = (new SmsMessage())
        ->setDirection(Direction::INCOMING)
        ->setGateway($sms_gateway)
        ->setResult($result);

      if (\array_key_exists('sender_number', $raw_message)) {
        $message->setSenderNumber($raw_message['sender_number']);
      }

      if (\array_key_exists('message', $raw_message)) {
        $message->setMessage($raw_message['message']);
      }

      if (\array_key_exists('recipients', $raw_message)) {
        $message->addRecipients($raw_message['recipients']);
      }

      $messages[] = $message;
    }

    $response = new Response('', 204);
    $task = (new SmsProcessingResponse())
      ->setResponse($response)
      ->setMessages($messages);

    return $task;
  }

}
