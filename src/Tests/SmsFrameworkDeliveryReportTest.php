<?php

namespace Drupal\sms\Tests;

use Drupal\Core\Url;
use Drupal\sms\Message\SmsDeliveryReportInterface;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Message\SmsMessageResultInterface;

/**
 * Integration tests for delivery reports.
 *
 * @group SMS Framework
 */
class SmsFrameworkDeliveryReportTest extends SmsFrameworkWebTestBase {

  /**
   * Tests delivery reports integration.
   */
  public function testDeliveryReports() {
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);

    $test_gateway = $this->createMemoryGateway(['skip_queue' => TRUE]);
    $sms_message = $this->randomSmsMessage($user->id())
      ->setGateway($test_gateway);

    $sms_messages = $this->defaultSmsProvider->send($sms_message);

    $result = $sms_messages[0]->getResult();
    $this->assertTrue($result instanceof SmsMessageResultInterface);
    $this->assertEqual(count($sms_message->getRecipients()), count($result->getReports()));
    $reports = $result->getReports();
    $first_report = reset($reports);
    $this->assertTrue($first_report instanceof SmsDeliveryReportInterface);
    $this->assertEqual($first_report->getStatus(), SmsMessageReportStatus::QUEUED);

    // Get the delivery reports url and simulate push delivery report.
    $url = Url::fromRoute('sms.process_delivery_report', ['sms_gateway' => $test_gateway->id()], ['absolute' => TRUE])->toString();
    $delivered_time = REQUEST_TIME;
    $delivery_report =<<<EOF
{
   "reports":[
      {
         "message_id":"{$first_report->getMessageId()}",
         "recipient":"{$first_report->getRecipient()}",
         "time_sent":{$first_report->getTimeSent()},
         "time_delivered": $delivered_time,
         "status": "800",
         "gateway_status": "THIS_HAS_BEEN_DELIVERED",
         "gateway_status_code": "202",
         "gateway_status_description": "Delivered to Handset"
      }
   ]
}
EOF;
    $this->drupalPost($url, 'application/json', ['delivery_report' => $delivery_report]);
    $this->assertText('custom response content');
    \Drupal::state()->resetCache();

    // Get the stored report and verify that it was properly parsed.
    $second_report = $this->getTestMessageReport($first_report->getMessageId(), $test_gateway);
    $this->assertEqual($first_report->getMessageId(), $second_report->getMessageId());
    $this->assertEqual("800", $second_report->getStatus());
    $this->assertEqual("THIS_HAS_BEEN_DELIVERED", $second_report->getGatewayStatus());
    $this->assertEqual($delivered_time, $second_report->getTimeDelivered());
  }

}
