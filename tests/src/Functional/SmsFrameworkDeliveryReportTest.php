<?php

namespace Drupal\Tests\sms\Functional;

use Drupal\sms\Message\SmsDeliveryReportInterface;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Message\SmsMessageResultInterface;

/**
 * Integration tests for delivery reports.
 *
 * @group SMS Framework
 * @group legacy
 */
class SmsFrameworkDeliveryReportTest extends SmsFrameworkBrowserTestBase {

  /**
   * Tests delivery reports integration.
   */
  public function testDeliveryReports() {
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);

    $test_gateway = $this->createMemoryGateway(['skip_queue' => TRUE]);
    $this->container->get('router.builder')->rebuild();
    $sms_message = $this->randomSmsMessage($user->id())
      ->setGateway($test_gateway);

    $sms_messages = $this->defaultSmsProvider->send($sms_message);

    $result = $sms_messages[0]->getResult();
    $this->assertTrue($result instanceof SmsMessageResultInterface);
    $this->assertEqual(count($sms_message->getRecipients()), count($result->getReports()));
    $reports = $result->getReports();

    /** @var \Drupal\sms\Message\SmsDeliveryReportInterface $first_report */
    $first_report = reset($reports);
    $message_id = $first_report->getMessageId();
    $this->assertTrue($first_report instanceof SmsDeliveryReportInterface);
    $this->assertEqual($first_report->getStatus(), SmsMessageReportStatus::QUEUED);

    // Get the delivery reports url and simulate push delivery report.
    $url = $test_gateway->getPushReportUrl()->setAbsolute()->toString();
    $delivered_time = REQUEST_TIME;
    $delivery_report = <<<EOF
{
   "reports":[
      {
         "message_id":"{$message_id}",
         "recipient":"{$first_report->getRecipient()}",
         "status":"delivered",
         "status_time": $delivered_time,
         "status_message": "status message"
      }
   ]
}
EOF;
    /** @var \Symfony\Component\BrowserKit\Client $client */
    $client = $this->getSession()->getDriver()->getClient();
    $client->request('post', $url, ['delivery_report' => $delivery_report]);
    $this->assertText('custom response content');
    \Drupal::state()->resetCache();

    // Get the stored report and verify that it was properly parsed.
    $second_report = $this->getTestMessageReport($message_id, $test_gateway);
    $this->assertTrue($second_report instanceof SmsDeliveryReportInterface);
    $this->assertEqual("status message", $second_report->getStatusMessage());
    $this->assertEqual($delivered_time, $second_report->getTimeDelivered());
    $this->assertEqual($message_id, $second_report->getMessageId());
  }

}
