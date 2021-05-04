<?php

declare(strict_types = 1);

namespace Drupal\Tests\sms\Kernel;

use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Tests pushing delivery reports to the site.
 *
 * @group SMS Framework
 */
class SmsFrameworkPushedDeliveryReportTest extends SmsFrameworkKernelBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system', 'sms', 'entity_test', 'user', 'field', 'telephone',
    'dynamic_entity_reference', 'sms_test_gateway',
  ];

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->routeProvider = $this->container->get('router.route_provider');
  }

  /**
   * Tests route exists for gateway with pushed reports.
   */
  public function testDeliveryReportRoute() {
    $gateway = $this->createMemoryGateway();
    $name = 'sms.delivery_report.receive.' . $gateway->id();
    $route = $this->routeProvider->getRouteByName($name);
    $this->assertEquals(
      $gateway->getPushReportPath(),
      $route->getPath()
    );
  }

  /**
   * Tests route access delivery report URL for gateway without pushed reports.
   */
  public function testDeliveryReportRouteNoSupportPush() {
    $gateway = $this->createMemoryGateway(['plugin' => 'capabilities_default']);
    $this->expectException(RouteNotFoundException::class);
    $route = 'sms.delivery_report.receive.' . $gateway->id();
    $this->routeProvider->getRouteByName($route);
  }

}
