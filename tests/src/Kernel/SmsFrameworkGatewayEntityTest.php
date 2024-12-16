<?php

declare(strict_types=1);

namespace Drupal\Tests\sms\Kernel;

use Drupal\Core\Url;
use Drupal\sms\Direction;
use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\Entity\SmsGatewayInterface;
use Drupal\sms_test_gateway\Plugin\SmsGateway\Incoming;
use Drupal\sms_test_gateway\Plugin\SmsGateway\LegacyAnnotation;

/**
 * Tests SMS Framework gateway entity.
 *
 * @group SMS Framework
 */
final class SmsFrameworkGatewayEntityTest extends SmsFrameworkKernelBase {

  protected static $modules = [
    'sms', 'sms_test_gateway', 'telephone', 'dynamic_entity_reference',
  ];

  /**
   * Tests skip queue.
   */
  public function testSkipQueue(): void {
    $gateway = $this->createGateway();
    static::assertFalse($gateway->getSkipQueue(), 'Default value does not skip queue.');

    $gateway->setSkipQueue(TRUE);
    static::assertTrue($gateway->getSkipQueue());
  }

  /**
   * Tests incoming retention setting.
   */
  public function testIncomingRetentionDuration(): void {
    $gateway = $this->createGateway();

    // Default value.
    static::assertEquals(0, $gateway->getRetentionDuration(Direction::INCOMING));

    $gateway->setRetentionDuration(Direction::INCOMING, 444);
    static::assertEquals(444, $gateway->getRetentionDuration(Direction::INCOMING));
  }

  /**
   * Tests outgoing retention setting.
   */
  public function testOutgoingRetentionDuration(): void {
    $gateway = $this->createGateway();

    // Default value.
    static::assertEquals(0, $gateway->getRetentionDuration(Direction::INCOMING));

    $gateway->setRetentionDuration(Direction::OUTGOING, 999);
    static::assertEquals(999, $gateway->getRetentionDuration(Direction::OUTGOING));
  }

  /**
   * Tests a bad retention direction.
   */
  public function testGetRetentionDurationInvalidDirection(): void {
    $gateway = $this->createGateway();
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('0 is not a valid direction.');
    // Intentionally invalid direction.
    // @phpstan-ignore-next-line
    $gateway->getRetentionDuration(0);
  }

  /**
   * Tests incoming message path.
   */
  public function testPushIncomingPath(): void {
    $gateway = $this->createGateway(['plugin' => 'incoming']);

    $path = $gateway->getPushIncomingPath() ?? throw new \Exception('Needs a path');
    static::assertTrue(\str_starts_with($path, '/sms/incoming/receive/'));

    $new_path = '/' . $this->randomMachineName();
    $return = $gateway->setPushIncomingPath($new_path);

    static::assertTrue($return instanceof SmsGatewayInterface);
    static::assertEquals($new_path, $gateway->getPushIncomingPath());
  }

  /**
   * Tests 'incoming' annotation custom value.
   */
  public function testSupportsIncoming(): void {
    $gateway = $this->createGateway(['plugin' => 'incoming']);
    static::assertTrue($gateway->supportsIncoming());
  }

  /**
   * Tests 'incoming' annotation default value.
   */
  public function testNotSupportsIncoming(): void {
    $gateway = $this->createGateway(['plugin' => 'capabilities_default']);
    static::assertFalse($gateway->supportsIncoming());
  }

  /**
   * Tests 'incoming_route' annotation custom value.
   */
  public function testAutoCreateIncomingRoute(): void {
    $gateway = $this->createGateway(['plugin' => Incoming::PLUGIN_ID]);
    static::assertTrue($gateway->autoCreateIncomingRoute());
  }

  /**
   * Tests 'incoming_route' annotation default value.
   */
  public function testNoAutoCreateIncomingRoute(): void {
    $gateway = $this->createGateway(['plugin' => 'capabilities_default']);
    static::assertFalse($gateway->autoCreateIncomingRoute());
  }

  /**
   * Tests gateways via annotations.
   */
  public function testLegacyAnnotation(): void {
    $gateway = $this->createGateway(['plugin' => LegacyAnnotation::PLUGIN_ID]);
    static::assertEquals(2, $gateway->getMaxRecipientsOutgoing());
    static::assertTrue($gateway->supportsIncoming());
    static::assertTrue($gateway->autoCreateIncomingRoute());
    static::assertTrue($gateway->isScheduleAware());
    static::assertTrue($gateway->supportsReportsPull());
    static::assertTrue($gateway->supportsReportsPush());
    static::assertTrue($gateway->supportsCreditBalanceQuery());
  }

  /**
   * Tests incoming report path.
   */
  public function testPushReportPath(): void {
    $gateway = $this->createGateway();

    $path = $gateway->getPushReportPath() ?? throw new \Exception('Needs a path');
    static::assertTrue(\str_starts_with($path, '/sms/delivery-report/receive/'));

    $new_path = '/' . $this->randomMachineName();
    $return = $gateway->setPushReportPath($new_path);

    static::assertTrue($return instanceof SmsGatewayInterface);
    static::assertEquals($new_path, $gateway->getPushReportPath());
  }

  /**
   * Tests incoming report url.
   */
  public function testPushReportUrl(): void {
    $gateway = $this->createGateway();
    static::assertTrue($gateway->getPushReportUrl() instanceof Url);
  }

  /**
   * Tests 'supports pushed reports' annotation custom value.
   */
  public function testSupportsReportsPushCustom(): void {
    $gateway = $this->createGateway([
      'plugin' => 'memory',
    ]);
    static::assertTrue($gateway->supportsReportsPush());
  }

  /**
   * Tests 'supports credit balance' annotation default value.
   */
  public function testSupportsReportsPushDefault(): void {
    $gateway = $this->createGateway([
      'plugin' => 'capabilities_default',
    ]);
    static::assertFalse($gateway->supportsReportsPush());
  }

  /**
   * Tests 'supports pulling reports' annotation custom value.
   */
  public function testSupportsReportsPullCustom(): void {
    $gateway = $this->createGateway([
      'plugin' => 'memory',
    ]);
    static::assertTrue($gateway->supportsReportsPull());
  }

  /**
   * Tests 'supports pulling balance' annotation default value.
   */
  public function testSupportsReportsPullDefault(): void {
    $gateway = $this->createGateway([
      'plugin' => 'capabilities_default',
    ]);
    static::assertFalse($gateway->supportsReportsPull());
  }

  /**
   * Tests 'max outgoing recipients' annotation custom value.
   */
  public function testGetMaxRecipientsOutgoingCustom(): void {
    $gateway = $this->createGateway([
      'plugin' => 'memory',
    ]);
    static::assertEquals(-1, $gateway->getMaxRecipientsOutgoing());
  }

  /**
   * Tests 'max outgoing recipients' annotation default value.
   */
  public function testGetMaxRecipientsOutgoingDefault(): void {
    $gateway = $this->createGateway([
      'plugin' => 'capabilities_default',
    ]);
    static::assertEquals(1, $gateway->getMaxRecipientsOutgoing());
  }

  /**
   * Tests 'incoming' annotation custom value.
   */
  public function testSupportsIncomingCustom(): void {
    $gateway = $this->createGateway([
      'plugin' => 'memory',
    ]);
    static::assertTrue($gateway->supportsIncoming());
  }

  /**
   * Tests 'incoming' annotation default value.
   */
  public function testSupportsIncomingDefault(): void {
    $gateway = $this->createGateway([
      'plugin' => 'capabilities_default',
    ]);
    static::assertFalse($gateway->supportsIncoming());
  }

  /**
   * Tests 'schedule aware annotation' custom value.
   */
  public function testIsScheduleAwareCustom(): void {
    $gateway = $this->createGateway([
      'plugin' => 'memory_schedule_aware',
    ]);
    static::assertTrue($gateway->isScheduleAware());
  }

  /**
   * Tests 'schedule aware annotation' default value.
   */
  public function testIsScheduleAwareDefault(): void {
    $gateway = $this->createGateway([
      'plugin' => 'capabilities_default',
    ]);
    static::assertFalse($gateway->isScheduleAware());
  }

  /**
   * Tests 'supports credit balance' annotation custom value.
   */
  public function testSupportsCreditBalanceQueryCustom(): void {
    $gateway = $this->createGateway([
      'plugin' => 'memory',
    ]);
    static::assertTrue($gateway->supportsCreditBalanceQuery());
  }

  /**
   * Tests 'supports credit balance' annotation default value.
   */
  public function testSupportsCreditBalanceQueryDefault(): void {
    $gateway = $this->createGateway([
      'plugin' => 'capabilities_default',
    ]);
    static::assertFalse($gateway->supportsCreditBalanceQuery());
  }

  /**
   * Create a new gateway.
   *
   * @param array $values
   *   Custom values to pass to the gateway.
   *
   * @return \Drupal\sms\Entity\SmsGatewayInterface
   *   An unsaved gateway config entity.
   */
  protected function createGateway(array $values = []): SmsGatewayInterface {
    return SmsGateway::create($values + [
      'plugin' => 'memory',
    ]);
  }

}
