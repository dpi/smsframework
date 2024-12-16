<?php

declare(strict_types=1);

namespace Drupal\Tests\sms\Functional;

use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Url;
use Drupal\sms_test_gateway\Plugin\SmsGateway\Incoming;

/**
 * Tests incoming route endpoint.
 *
 * @group SMS Framework
 * @phpstan-import-type JsonRequestItem from \Drupal\sms_test_gateway\Plugin\SmsGateway\Incoming
 */
final class SmsFrameworkIncomingBrowserTest extends SmsFrameworkBrowserTestBase {

  /**
   * Test incoming route endpoint provided by 'incoming' gateway.
   */
  public function testIncomingRouteEndpoint(): void {
    $incomingGateway = $this->createMemoryGateway(['plugin' => Incoming::PLUGIN_ID]);
    $incomingGateway
      ->setSkipQueue(TRUE)
      ->save();
    \Drupal::service(RouteBuilderInterface::class)->rebuild();

    /** @var JsonRequestItem[] $messages */
    $messages = [];
    $messages[] = [
      'message' => $this->randomString(),
      'recipients' => $this->randomPhoneNumbers(),
    ];
    $messages[] = [
      'message' => $this->randomString(),
      'recipients' => $this->randomPhoneNumbers(),
    ];

    $url = Url::fromRoute(\sprintf('sms.incoming.receive.%s', $incomingGateway->id()))
      ->setRouteParameter('sms_gateway', $incomingGateway->id())
      ->setAbsolute()
      ->toString();

    $response = \Drupal::httpClient()->post($url, [
      'json' => [
        'messages' => $messages,
      ],
    ]);

    static::assertEquals(204, $response->getStatusCode(), 'HTTP code is 204');
    static::assertEmpty((string) $response->getBody(), 'Response body is empty.');

    $incoming_messages = $this->getIncomingMessages($incomingGateway);
    static::assertCount(\count($messages), $incoming_messages, 'There are 2 messages');
    foreach ($messages as $i => $message) {
      static::assertEquals($message['message'] ?? NULL, $incoming_messages[$i]->getMessage(), "Message $i contents are same.");
      static::assertEquals($message['recipients'] ?? [], $incoming_messages[$i]->getRecipients(), "Message $i recipients are same.");
    }
  }

}
