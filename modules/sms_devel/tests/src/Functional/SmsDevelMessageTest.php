<?php

namespace Drupal\Tests\sms_devel\Functional;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Drupal\Tests\sms\Functional\SmsFrameworkBrowserTestBase;
use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Direction;

/**
 * Tests the message form.
 *
 * @group SMS Framework
 */
class SmsDevelMessageTest extends SmsFrameworkBrowserTestBase {

  public static $modules = ['sms_devel'];

  /**
   * A memory gateway.
   *
   * @var \Drupal\sms\Entity\SmsGatewayInterface
   */
  protected $gateway;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $user = $this->drupalCreateUser(['sms_devel form']);
    $this->drupalLogin($user);

    $this->gateway = $this->createMemoryGateway();
    $this->setFallbackGateway($this->gateway);
  }

  /**
   * Tests sending functionality skipping queue.
   */
  public function testSendSkipQueue() {
    $edit['number'] = $this->randomPhoneNumbers(1)[0];
    $edit['message'] = $this->randomString();
    $edit['skip_queue'] = TRUE;

    $this->drupalPostForm(Url::fromRoute('sms_devel.message'), $edit, t('Send'));
    $this->assertResponse(200);
    $this->assertRaw('Message was processed, 1 delivery reports were generated.');

    $messages = $this->getTestMessages($this->gateway);
    $this->assertEqual(1, count($messages));
    $this->assertEqual($edit['message'], $messages[0]->getMessage());
  }

  /**
   * Tests sending functionality entering queue.
   */
  public function testSendNoSkipQueue() {
    $edit['number'] = $this->randomPhoneNumbers(1)[0];
    $edit['message'] = $this->randomString();
    $edit['skip_queue'] = FALSE;

    $this->drupalPostForm(Url::fromRoute('sms_devel.message'), $edit, t('Send'));
    $this->assertResponse(200);
    $this->assertRaw('Message added to the outgoing queue.');

    $messages = SmsMessage::loadMultiple();
    $message = reset($messages);
    $this->assertEqual($edit['message'], $message->getMessage(), 'Message is same');
    $this->assertEqual(Direction::OUTGOING, $message->getDirection(), 'Message is outgoing');
  }

  /**
   * Tests receiving functionality skipping queue.
   */
  public function testReceiveSkipQueue() {
    $edit['number'] = $this->randomPhoneNumbers(1)[0];
    $edit['message'] = $this->randomString();
    $edit['gateway'] = $this->gateway->id();
    $edit['skip_queue'] = TRUE;

    $this->drupalPostForm(Url::fromRoute('sms_devel.message'), $edit, t('Receive'));
    $this->assertResponse(200);
    $this->assertRaw('Message was processed, 1 delivery reports were generated.');

    $this->assertEqual($edit['message'], sms_test_gateway_get_incoming()['message']);
  }

  /**
   * Tests receiving functionality entering queue.
   */
  public function testReceiveNoSkipQueue() {
    $edit['number'] = $this->randomPhoneNumbers(1)[0];
    $edit['message'] = $this->randomString();
    $edit['gateway'] = $this->gateway->id();
    $edit['skip_queue'] = FALSE;

    $this->drupalPostForm(Url::fromRoute('sms_devel.message'), $edit, t('Receive'));
    $this->assertResponse(200);
    $this->assertRaw('Message added to the incoming queue.');

    $messages = SmsMessage::loadMultiple();
    $message = reset($messages);
    $this->assertEqual($edit['message'], $message->getMessage(), 'Message is same');
    $this->assertEqual(Direction::INCOMING, $message->getDirection(), 'Message is incoming');
  }

  /**
   * Tests receiving with no selected gateway.
   */
  public function testReceiveGatewayInvalid() {
    $edit['gateway'] = '';

    $this->drupalPostForm(Url::fromRoute('sms_devel.message'), $edit, t('Receive'));
    $this->assertResponse(200);
    $this->assertRaw('Gateway must be selected if receiving a message.');
  }

  /**
   * Tests tagging message as automated.
   */
  public function testAutomated() {
    $edit['number'] = $this->randomPhoneNumbers(1)[0];
    $edit['message'] = $this->randomString();
    $edit['skip_queue'] = FALSE;
    $edit['automated'] = FALSE;

    $this->drupalPostForm(Url::fromRoute('sms_devel.message'), $edit, t('Send'));
    $this->assertResponse(200);

    $messages = SmsMessage::loadMultiple();
    $message = reset($messages);
    $this->assertFalse($message->isAutomated(), 'Message is not automated');
  }

  /**
   * Tests adding send date.
   */
  public function testDate() {
    $edit['number'] = $this->randomPhoneNumbers(1)[0];
    $edit['message'] = $this->randomString();
    $edit['skip_queue'] = FALSE;

    $value = '2005-11-25 22:03:58';
    $date = new DrupalDateTime($value, 'UTC');
    // The user inputs field values in its own timezone, then it is auto
    // converted on field submission to UTC.
    $date_user = $date;
    $date_user->setTimezone(timezone_open(drupal_get_user_timezone()));
    $edit['send_on[date]'] = $date_user->format('Y-m-d');
    $edit['send_on[time]'] = $date_user->format('H:i:s');
    $this->drupalPostForm(Url::fromRoute('sms_devel.message'), $edit, t('Send'));
    $this->assertResponse(200);

    $messages = SmsMessage::loadMultiple();
    $message = reset($messages);
    $this->assertEqual($date->format('U'), $message->getSendTime(), 'Message has send time.');
  }

  /**
   * Tests error shown if gateway found for message.
   */
  public function testNoFallbackGateway() {
    $this->setFallbackGateway(NULL);

    $edit['number'] = $this->randomPhoneNumbers(1)[0];
    $edit['message'] = $this->randomString();
    $edit['skip_queue'] = TRUE;

    $this->drupalPostForm(Url::fromRoute('sms_devel.message'), $edit, t('Send'));
    $this->assertResponse(200);
    $this->assertRaw('Message could not be sent');

    $messages = $this->getTestMessages($this->gateway);
    $this->assertEqual(0, count($messages), 'No messages sent.');
  }

}
