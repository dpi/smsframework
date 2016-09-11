<?php

namespace Drupal\Tests\sms\Kernel;

use Drupal\Core\Url;
use Drupal\Core\Session\AnonymousUserSession;

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
   * An anonymous user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $anonymous;

  /**
   * @inheritdoc
   */
  protected function setUp() {
    parent::setUp();
    $this->accessManager = $this->container->get('access_manager');
    $this->anonymous = new AnonymousUserSession();
  }

  /**
   * Tests route access delivery report URL for gateway with pushed reports.
   */
  public function testDeliveryReportRoute() {
    $gateway = $this->createMemoryGateway();
    $url = Url::fromRoute('sms.process_delivery_report', ['sms_gateway' => $gateway->id()]);
    $access = $this->accessManager->checkNamedRoute($url->getRouteName(), $url->getRouteParameters(), $this->anonymous);
    $this->assertTrue($access, 'Access to delivery report URL is allowed.');
  }

  /**
   * Tests route access delivery report URL for gateway without pushed reports.
   */
  public function testDeliveryReportRouteNoSupportPush() {
    $gateway = $this->createMemoryGateway(['plugin' => 'capabilities_default']);
    $url = Url::fromRoute('sms.process_delivery_report', ['sms_gateway' => $gateway->id()]);
    $access = $this->accessManager->checkNamedRoute($url->getRouteName(), $url->getRouteParameters(), $this->anonymous);
    $this->assertFalse($access, 'Access to delivery report URL is forbidden.');
  }

}
