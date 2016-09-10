<?php

namespace Drupal\Tests\sms\Kernel;

use Drupal\Core\Url;

/**
 * Tests pushing delivery reports to the site.
 *
 * @group SMS Framework
 */
class SmsFrameworkPushedDeliveryReportTest extends SmsFrameworkKernelBase {

  /**
   * @inheritdoc
   */
  public static $modules = ['system', 'sms', 'entity_test', 'user', 'field', 'telephone', 'dynamic_entity_reference', 'sms_test_gateway'];

  /**
   * The access manager service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * @inheritdoc
   */
  protected function setUp() {
    parent::setUp();
    $this->accessManager = $this->container->get('access_manager');
  }

  /**
   * Tests route access delivery report URL for gateway with pushed reports.
   */
  public function testDeliveryReportRoute() {
    $gateway = $this->createMemoryGateway();
    $url = Url::fromRoute('sms.process_delivery_report', ['sms_gateway' => $gateway->id()]);
    $access = $this->accessManager->checkNamedRoute($url->getRouteName(), $url->getRouteParameters());
    $this->assertTrue($access, 'Access to delivery report URL is allowed.');
  }

  /**
   * Tests route access delivery report URL for gateway without pushed reports.
   */
  public function testDeliveryReportRouteNoSupportPush() {
    $gateway = $this->createMemoryGateway(['plugin' => 'capabilities_default']);
    $url = Url::fromRoute('sms.process_delivery_report', ['sms_gateway' => $gateway->id()]);
    $access = $this->accessManager->checkNamedRoute($url->getRouteName(), $url->getRouteParameters());
    $this->assertFalse($access, 'Access to delivery report URL is forbidden.');
  }

}
