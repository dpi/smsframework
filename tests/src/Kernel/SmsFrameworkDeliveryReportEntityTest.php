<?php

namespace Drupal\Tests\sms\Kernel;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\sms\Entity\SmsDeliveryReport;
use Drupal\sms\Entity\SmsDeliveryReportInterface;
use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\Tests\sms\Functional\SmsFrameworkDeliveryReportTestTrait;
use Drupal\Tests\sms\Functional\SmsFrameworkTestTrait;

/**
 * Tests the SMS Delivery report entity.
 *
 * @group SMS Framework
 * @group legacy
 * @coversDefaultClass \Drupal\sms\Entity\SmsDeliveryReport
 */
class SmsFrameworkDeliveryReportEntityTest extends KernelTestBase  {

  use SmsFrameworkTestTrait;
  use SmsFrameworkDeliveryReportTestTrait {
    // Remove 'test' prefix so it will not be run by test runner and override.
    testTimeQueued as timeQueued;
    testTimeDelivered as timeDelivered;
  }

  public static $modules = ['user', 'sms', 'sms_test_gateway', 'telephone', 'dynamic_entity_reference', 'entity_test'];

  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installEntitySchema('sms');
    $this->installEntitySchema('sms_result');
    $this->installEntitySchema('sms_report');
  }

  /**
   * {@inheritdoc}
   */
  protected function createDeliveryReport() {
    return SmsDeliveryReport::create();
  }

  /**
   * Tests time queued.
   *
   * @covers ::getTimeQueued
   * @covers ::setTimeQueued
   */
  public function testTimeQueued() {
    $report = $this->createDeliveryReport();
    $this->assertNull($report->getTimeQueued(), 'Default value is NULL');

    // Save a version that has QUEUED as the status.
    $sms_message = SmsMessage::create();
    $sms_message->save();
    $time = 123123123;
    $report
      ->setSmsMessage($sms_message)
      ->setStatus(SmsMessageReportStatus::QUEUED)
      ->setStatusTime($time)
      ->save();

    $return = $report
      ->setTimeQueued($time);

    $this->assertTrue($return instanceof SmsDeliveryReportInterface);
    $this->assertEquals($time, $report->getTimeQueued());
  }

  /**
   * Tests time delivered.
   *
   * @covers ::getTimeDelivered
   * @covers ::setTimeDelivered
   */
  public function testTimeDelivered() {
    $report = $this->createDeliveryReport();
    $this->assertNull($report->getTimeQueued(), 'Default value is NULL');

    // Save a version that has DELIVERED as the status.
    $sms_message = SmsMessage::create();
    $sms_message->save();
    $time = 123123123;
    $report
      ->setSmsMessage($sms_message)
      ->setStatus(SmsMessageReportStatus::DELIVERED)
      ->setStatusTime($time)
      ->save();

    $return = $report
      ->setTimeDelivered($time);

    $this->assertTrue($return instanceof SmsDeliveryReportInterface);
    $this->assertEquals($time, $report->getTimeDelivered());
  }

  /**
   * Tests saving and retrieval of a complete entity.
   *
   * @covers ::save
   */
  public function testSaveAndRetrieveReport() {
    /** @var \Drupal\sms\Entity\SmsDeliveryReport $report */
    $report = $this->createDeliveryReport()
      ->setMessageId($this->randomMachineName())
      ->setStatus(SmsMessageReportStatus::DELIVERED)
      ->setRecipient('1234567890')
      ->setStatusMessage('Message delivered')
      ->setStatusTime($this->container->get('datetime.time')->getRequestTime());

    $sms_message = SmsMessage::create();
    $sms_message->save();
    $report
      ->setSmsMessage($sms_message)
      ->save();

    $storage = $this->container->get('entity_type.manager')->getStorage('sms_report');
    $saved = $storage->loadByProperties([
      'recipient' => '1234567890',
    ]);
    $this->assertEquals(1, count($saved));
    $saved = reset($saved);
    $this->assertEquals($report->getRecipient(), $saved->getRecipient());
    $this->assertEquals($report->getMessageId(), $saved->getMessageId());
    $this->assertEquals($report->getStatus(), $saved->getStatus());
    $this->assertEquals($report->getStatusMessage(), $saved->getStatusMessage());
    $this->assertEquals($report->getStatusTime(), $saved->getStatusTime());
    $this->assertEquals($report->uuid(), $saved->uuid());
  }

  /**
   * Tests saving a delivery report without a parent SMS message.
   *
   * @covers ::save
   * @covers ::preSave
   */
  public function testSaveReportWithoutParent() {
    $this->setExpectedException(EntityStorageException::class, 'No parent SMS message specified for SMS delivery report');
    /** @var \Drupal\sms\Entity\SmsMessageResult $result */
    $result = $this->createDeliveryReport()
      ->setMessageId($this->randomMachineName())
      ->setStatus(SmsMessageReportStatus::DELIVERED)
      ->setRecipient('1234567890')
      ->setStatusMessage('Message delivered')
      ->setStatusTime($this->container->get('datetime.time')->getRequestTime());
    $result->save();
  }

  /**
   * Test saving of delivery report revisions.
   */
  public function testReportRevisions() {
    $sms_message = SmsMessage::create();
    $sms_message->save();

    $time_queued = $this->container->get('datetime.time')->getRequestTime();
    $time_delivered = $time_queued + 3600;

    /** @var \Drupal\sms\Entity\SmsDeliveryReport $report */
    $report = $this->createDeliveryReport()
      ->setSmsMessage($sms_message)
      ->setMessageId($this->randomMachineName())
      ->setStatus(SmsMessageReportStatus::QUEUED)
      ->setRecipient('1234567890')
      ->setStatusMessage('Message queued')
      ->setStatusTime($time_queued);
    $report->save();

    $report
      ->setStatus(SmsMessageReportStatus::DELIVERED)
      ->setStatusMessage('Message delivered')
      ->setStatusTime($time_delivered)
      ->save();

    $this->assertEquals($time_queued, $report->getTimeQueued());
    $this->assertEquals($time_delivered, $report->getTimeDelivered());
  }

  /**
   * Tests the multiple revisioning of delivery reports.
   *
   * @covers ::getRevisionAtStatus
   */
  public function testMultipleReportRevisions() {
    $sms_message = SmsMessage::create();
    $sms_message->save();

    $request_time = $this->container->get('datetime.time')->getRequestTime();
    $status_times = [
      'queued' => $request_time,
      'pending' => $request_time + 1800,
      'delivered' => $request_time + 3600,
    ];
    /** @var \Drupal\sms\Entity\SmsDeliveryReport $report */
    $report = $this->createDeliveryReport()
      ->setSmsMessage($sms_message);

    foreach ($status_times as $status => $time) {
      $report
        ->setStatus($status)
        ->setStatusMessage('Status ' . $status)
        ->setStatusTime($time)
        ->save();
    }

    $this->assertEquals($status_times['queued'], $report->getRevisionAtStatus('queued')->getStatusTime());
    $this->assertEquals($status_times['pending'], $report->getRevisionAtStatus('pending')->getStatusTime());
    $this->assertEquals($status_times['delivered'], $report->getRevisionAtStatus('delivered')->getStatusTime());

    // Create another revision with different status time.
    $report
      ->setStatus('queued')
      ->setStatusTime(1234567890)
      ->save();

    // Verify that the latest revision is always returned.
    $this->assertEquals(1234567890, $report->getRevisionAtStatus('queued')->getStatusTime());
    $this->assertEquals($status_times['pending'], $report->getRevisionAtStatus('pending')->getStatusTime());
    $this->assertEquals($status_times['delivered'], $report->getRevisionAtStatus('delivered')->getStatusTime());
  }

}
