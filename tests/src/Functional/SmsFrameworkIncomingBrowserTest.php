<?php

namespace Drupal\Tests\sms\Functional;

use Drupal\Core\Url;

/**
 * Tests manage event access page.
 *
 * @group SMS Framework
 */
class SmsFrameworkIncomingBrowserTest extends SmsFrameworkBrowserTestBase {

  /**
   * The router builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * An incoming gateway instance.
   *
   * @var \Drupal\sms\Entity\SmsGatewayInterface
   */
  protected $incomingGateway;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->routerBuilder = $this->container->get('router.builder');
    $this->httpClient = \Drupal::httpClient();//$this->container->get('http_client');

    $this->incomingGateway = $this->createMemoryGateway(['plugin' => 'incoming']);
    $this->incomingGateway
      ->setSkipQueue(TRUE)
      ->save();;
    $this->routerBuilder->rebuild();
  }

  public function testIncomingRouteEndpoint() {
    $messages = [];
    $messages[0] = [
      'message' => $this->randomString(),
      'recipients' => $this->randomPhoneNumbers(),
    ];

    $url = Url::fromRoute('sms.incoming.receive.' . $this->incomingGateway->id())
      ->setRouteParameter('sms_gateway', $this->incomingGateway->id())
      ->setAbsolute();

    $options = [
      'json' => [
        'messages' => $messages,
      ],
      'http_errors' => FALSE,
    ];

    $response = $this->httpClient
      ->post($url->toString(), $options);

    $this->assertEquals(204, $response->getStatusCode(), 'HTTP code is 204');
    $this->assertEmpty((string) $response->getBody(), 'Response body is empty.');

    $key = 'sms_test_gateway_incoming_messages';
    $incoming_messages = \Drupal::state()->get($key, []);
    $this->assertEquals(count($messages), count($incoming_messages), 'There are 1 messages');
  }

}
