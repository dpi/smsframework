<?php

/**
 * @file
 * Contains \Drupal\sms\Tests\SmsFrameworkWebTest.
 */

namespace Drupal\sms\Tests;

use Drupal\sms\Message\SmsDeliveryReportInterface;
use Drupal\sms\Message\SmsMessageResultInterface;

/**
 * Integration tests for the SMS Framework.
 *
 * @group SMS Framework
 */
class SmsFrameworkWebTest extends SmsFrameworkWebTestBase {

  /**
   * Tests delivery reports integration.
   */
  public function testDeliveryReports() {
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    $sms_message = $this->randomSmsMessage($user->id());

    $result = $this->defaultSmsProvider->send($sms_message, ['gateway' => $this->testGateway->id()]);

    $this->assertTrue($result instanceof SmsMessageResultInterface);
    $this->assertEqual(count($sms_message->getRecipients()), count($result->getReports()));
    $reports = $result->getReports();
    $first_report = reset($reports);
    $this->assertTrue($first_report instanceof SmsDeliveryReportInterface);
    $this->assertEqual($first_report->getStatus(), SmsDeliveryReportInterface::STATUS_SENT);

    // Get the delivery reports url and simulate push delivery report.
    $url = $this->testGateway->getPlugin()->getDeliveryReportPath();
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
    $second_report = $this->getTestMessageReport($first_report->getMessageId());
    $this->assertEqual($first_report->getMessageId(), $second_report->getMessageId());
    $this->assertEqual("800", $second_report->getStatus());
    $this->assertEqual("THIS_HAS_BEEN_DELIVERED", $second_report->getGatewayStatus());
    $this->assertEqual($delivered_time, $second_report->getTimeDelivered());
  }

  /**
   * Tests basic number validation.
   */
  public function testNumberValidation() {
    $test_numbers = array(
      '1234567890' => true,
      '123458767890' => true,
      '389427-9238' => true,
      '=-,x2-4n292' => true,
      ';ajklf a/s,MFA' => false,
      '] W[OPQIRW' => false,
      '9996789065' => true,
      '1234567890987654' => true,
    );

    // Test validation with default gateway (log).
    foreach ($test_numbers as $number => $valid) {
      $result = sms_validate_number($number);
      $this->assertEqual($valid, empty($result), 'Number validation ok for ' . $number);
    }
  }

  /**
   * Tests basic number validation.
   */
//  public function testNumberValidationWithGateway() {
//    // @todo, reimplement number validation. TBD.
//    $test_numbers = array(
//      '1234567890' => true,
//      '123458767890' => true,
//      '389427-9238' => false,
//      '=-,x2-4n292' => false,
//      ';ajklf a/s,MFA' => false,
//      '] W[OPQIRW' => false,
//      '9996789065' => false,
//      '1234567890987654' => false,
//    );
//
//    foreach ($test_numbers as $number => $valid) {
//      $result = sms_validate_number($number, ['gateway' => 'test']);
//      $this->assertEqual($valid, empty($result), 'Number validation ok for ' . $number);
//    }
//  }

}
