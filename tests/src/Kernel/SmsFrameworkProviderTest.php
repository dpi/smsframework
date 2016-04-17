<?php

/**
 * @file
 * Contains \Drupal\Tests\sms\Kernel\SmsFrameworkProviderTest.
 */

namespace Drupal\Tests\sms\Kernel;

use Drupal\sms\Message\SmsMessage as StandardSmsMessage;
use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Entity\SmsMessageInterface;
use Drupal\sms\Entity\SmsGateway;

/**
 * Tests SMS Framework provider service.
 *
 * @group SMS Framework
 * @coversDefaultClass \Drupal\sms\Provider\SmsProviderInterface
 */
class SmsFrameworkProviderTest extends SmsFrameworkKernelBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['sms', 'sms_test_gateway', 'field', 'telephone', 'dynamic_entity_reference'];

  /**
   * @var \Drupal\sms\Provider\SmsProviderInterface
   *
   * The default SMS provider.
   */
  protected $smsProvider;

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

    $this->installEntitySchema('sms');

    $this->gateway = $this->createMemoryGateway();
    $this->smsProvider = $this->container->get('sms_provider');
    $this->smsProvider->setDefaultGateway($this->gateway);
  }

  /**
   * Test message is sent immediately.
   *
   * @covers ::send
   */
  public function testSend() {
    $this->smsProvider->send($this->createSmsMessage(), []);
    $this->assertEquals(1, count($this->getTestMessages($this->gateway)));
  }

  /**
   * Test message is saved.
   */
  public function testQueueBasic() {
    $sms_message = $this->createSmsMessage();
    $this->smsProvider->queue($sms_message);
    $this->assertEquals(1, count(SmsMessage::loadMultiple()), 'SMS message saved.');
  }

  /**
   * Test message is sent immediately.
   */
  public function testSkipQueue() {
    $this->gateway
      ->setSkipQueue(TRUE)
      ->save();
    $sms_message = $this->createSmsMessage();
    $this->smsProvider->queue($sms_message);
    $this->assertEquals(1, count($this->getTestMessages($this->gateway)));
  }

  /**
   * Test sending standard SMS object queue in.
   */
  public function testQueueIn() {
    $sms_message = new StandardSmsMessage('', [], '', [], NULL);
    $sms_message
      ->addRecipients($this->randomPhoneNumbers())
      ->setMessage($this->randomString());

    $this->smsProvider->queueIn($sms_message);

    $sms_messages = SmsMessage::loadMultiple();
    $this->assertEquals(1, count($sms_messages), 'There is one SMS message in the queue.');

    $sms_message_loaded = reset($sms_messages);
    $this->assertEquals(SmsMessageInterface::DIRECTION_INCOMING, $sms_message_loaded->getDirection());
  }

  /**
   * Test sending standard SMS object queue out.
   */
  public function testQueueOut() {
    $sms_message = new StandardSmsMessage('', [], '', [], NULL);
    $sms_message
      ->addRecipients($this->randomPhoneNumbers())
      ->setMessage($this->randomString());

    $this->smsProvider->queueOut($sms_message);

    $sms_messages = SmsMessage::loadMultiple();
    $this->assertEquals(1, count($sms_messages), 'There is one SMS message in the queue.');

    $sms_message_loaded = reset($sms_messages);
    $this->assertEquals(SmsMessageInterface::DIRECTION_OUTGOING, $sms_message_loaded->getDirection());
  }

  /**
   * Test sending standard SMS object queue out skips queue.
   */
  public function testQueueOutSkipQueue() {
    $this->gateway
      ->setSkipQueue(TRUE)
      ->save();

    $sms_message = new StandardSmsMessage('', [], '', [], NULL);
    $sms_message
      ->addRecipients($this->randomPhoneNumbers())
      ->setMessage($this->randomString());

    $this->smsProvider->queueOut($sms_message);
    $this->assertEquals(1, count($this->getTestMessages($this->gateway)), 'One standard SMS send skipped queue.');
  }

  /**
   * Test message is split into multiple messages if gateway demands it.
   */
  public function testChunking() {
    $gateway_chunked = SmsGateway::create([
      'plugin' => 'memory_chunked',
      'id' => 'memory_chunked',
      'settings' => ['gateway_id' => 'memory_chunked'],
    ]);
    $gateway_chunked->enable()->save();

    $sms_message = $this->createSmsMessage()
      ->setGateway($gateway_chunked)
      ->addRecipients(['123123123', '456456456', '789789789']);
    $this->smsProvider->queue($sms_message);

    $this->assertEquals(2, count(SmsMessage::loadMultiple()), 'One SMS message has been split into two.');
  }

  /**
   * Test message is not into multiple messages if gateway defines no chunking.
   */
  public function testNoChunking() {
    $sms_message = $this->createSmsMessage()
      ->setGateway($this->gateway)
      ->addRecipients($this->randomPhoneNumbers());

    $this->smsProvider->queue($sms_message);

    $this->assertEquals(1, count(SmsMessage::loadMultiple()), 'SMS message has not been split.');
  }

  /**
   * Create a SMS message entity for testing.
   *
   * @param array $values
   *   An mixed array of values to pass when creating the SMS message entity.
   *
   * @return \Drupal\sms\Entity\SmsMessageInterface
   */
  protected function createSmsMessage(array $values = []) {
    return SmsMessage::create($values)
      ->setDirection(SmsMessageInterface::DIRECTION_OUTGOING)
      ->setMessage($this->randomString());
  }

}
