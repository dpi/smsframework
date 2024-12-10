<?php

declare(strict_types=1);

namespace Drupal\Tests\sms\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\KernelTests\KernelTestBase;
use Drupal\sms\Direction;
use Drupal\sms\Entity\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessage;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Provider\SmsProviderInterface;
use Drupal\Tests\sms\Trait\SmsFrameworkTestTrait;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the update of SMS Delivery report entities.
 *
 * @group SMS Framework
 */
final class SmsFrameworkDeliveryReportUpdateTest extends KernelTestBase {

  use SmsFrameworkTestTrait;

  protected static $modules = [
    'sms',
    'sms_test_gateway',
    'telephone',
    'dynamic_entity_reference',
    'user',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('sms');
    $this->installEntitySchema('sms_result');
    $this->installEntitySchema('sms_report');
    $this->installEntitySchema('user');
  }

  /**
   * Tests that delivery reports are updated after initial sending.
   */
  public function testDeliveryReportUpdate(): void {
    $user = User::create(['name' => $this->randomMachineName()]);
    $user->save();
    $request_time = \Drupal::service('datetime.time')->getRequestTime();

    $test_gateway = $this->createMemoryGateway();
    $test_gateway
      ->setRetentionDuration(Direction::OUTGOING, 1000)
      ->save();
    \Drupal::service('router.builder')->rebuild();

    $sms_message = (new SmsMessage())
      ->setSender($this->randomMachineName())
      ->addRecipients(['1234567890', '987654321'])
      ->setMessage($this->randomString())
      ->setUid((int) $user->id())
      ->setGateway($test_gateway)
      ->setDirection(Direction::OUTGOING);

    static::smsProvider()->queue($sms_message);
    \Drupal::service('cron')->run();
    $saved_reports = SmsDeliveryReport::loadMultiple();
    static::assertCount(2, $saved_reports);
    static::assertEquals(SmsMessageReportStatus::QUEUED, $saved_reports[1]->getStatus());
    static::assertEquals(SmsMessageReportStatus::QUEUED, $saved_reports[2]->getStatus());

    /** @var \Drupal\sms\Message\SmsDeliveryReportInterface $first_report */
    $first_report = \reset($saved_reports);
    static::assertEquals($request_time, $first_report->getStatusTime());

    $message_id = $first_report->getMessageId();
    $status_time = $request_time + 100;

    // Simulate push delivery report.
    $request = $this->buildDeliveryReportRequest($message_id, $first_report->getRecipient(), 'pending', $status_time);
    static::smsProvider()->processDeliveryReport($request, $test_gateway);
    \Drupal::service('entity_type.manager')->getStorage('sms_report')->resetCache();
    $updated_report = SmsDeliveryReport::load($first_report->id());
    static::assertEquals('pending', $updated_report->getStatus());
    static::assertEquals($status_time, $updated_report->getStatusTime());
    static::assertNull($updated_report->getTimeDelivered());

    // Simulate push delivery report.
    $status_time = $request_time + 500;
    $request = $this->buildDeliveryReportRequest($message_id, $first_report->getRecipient(), SmsMessageReportStatus::DELIVERED, $status_time);
    static::smsProvider()->processDeliveryReport($request, $test_gateway);
    \Drupal::service('entity_type.manager')->getStorage('sms_report')->resetCache();
    $updated_report = SmsDeliveryReport::load($first_report->id());
    static::assertEquals(SmsMessageReportStatus::DELIVERED, $updated_report->getStatus());
    static::assertEquals($status_time, $updated_report->getStatusTime());
    static::assertEquals($status_time, $updated_report->getTimeDelivered());
  }

  /**
   * Builds a request containing a JSON-encoded delivery report.
   *
   * @param string $message_id
   *   The delivery report message ID.
   * @param string $recipient
   *   The delivery report recipient number.
   * @param string $status
   *   The message delivery status.
   * @param int $status_time
   *   The time for the current status update.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   A request object containing JSON-encoded delivery reports.
   */
  protected function buildDeliveryReportRequest($message_id, $recipient, $status, $status_time): Request {
    $reports[] = [
      'message_id' => $message_id,
      'recipient' => $recipient,
      'status' => $status,
      'status_time' => $status_time,
      'status_message' => 'Message ' . $status,
    ];
    $request = new Request();
    $request->request->set('delivery_report', Json::encode(['reports' => $reports]));
    return $request;
  }

  private static function smsProvider(): SmsProviderInterface {
    return \Drupal::service(SmsProviderInterface::class);
  }

}
