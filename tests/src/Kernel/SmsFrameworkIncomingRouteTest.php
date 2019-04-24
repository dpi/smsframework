<?php

namespace Drupal\Tests\sms\Kernel;

use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Tests incoming routes for gateway plugins.
 *
 * @group SMS Framework
 */
class SmsFrameworkIncomingRouteTest extends SmsFrameworkKernelBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system', 'sms', 'entity_test', 'user', 'field', 'telephone',
    'dynamic_entity_reference', 'sms_test_gateway', 'basic_auth',
  ];

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

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
    $this->routeProvider = $this->container->get('router.route_provider');
  }

  /**
   * Tests route does not exist for gateway without incoming route.
   */
  public function testIncomingRouteUnsupported() {
    $gateway = $this->createMemoryGateway(['plugin' => 'capabilities_default']);
    $this->setExpectedException(RouteNotFoundException::class);
    $route = 'sms.incoming.receive.' . $gateway->id();
    $this->routeProvider->getRouteByName($route);
  }

  /**
   * Tests route exists for gateway with incoming route annotation.
   */
  public function testIncomingRoute() {
    $incoming_gateway = $this->createMemoryGateway(['plugin' => 'incoming']);
    $route = 'sms.incoming.receive.' . $incoming_gateway->id();
    $this->routeProvider->getRouteByName($route);
  }

}
