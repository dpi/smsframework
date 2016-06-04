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
use Drupal\sms\Direction;

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
  public static $modules = ['sms', 'sms_test', 'sms_test_gateway', 'field', 'telephone', 'dynamic_entity_reference'];

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
    $message = $this->createSmsMessage()
      ->addRecipients($this->randomPhoneNumbers());
    $this->smsProvider->send($message);
    $this->assertEquals(1, count($this->getTestMessages($this->gateway)));
  }

  /**
   * Ensure no messages sent if no recipients.
   */
  public function testNoSendNoRecipients() {
    $sms_message = SmsMessage::create()
      ->setDirection(Direction::OUTGOING)
      ->setMessage($this->randomString());
    $this->smsProvider->send($sms_message);
    $this->assertEquals(0, count($this->getTestMessages($this->gateway)));
  }

  /**
   * Test message is not sent because no gateway is set.
   *
   * @covers ::send
   */
  public function testSendNoFallbackGateway() {
    $this->smsProvider->setDefaultGateway(NULL);
    $this->setExpectedException(\Drupal\sms\Exception\RecipientRouteException::class);
    $message = $this->createSmsMessage()
      ->addRecipients($this->randomPhoneNumbers());
    $this->smsProvider->send($message);
  }

  /**
   * Test message is saved.
   */
  public function testQueueBasic() {
    $sms_message = $this->createSmsMessage()
      ->addRecipients($this->randomPhoneNumbers());
    $return = $this->smsProvider->queue($sms_message);
    $this->assertEquals(1, count(SmsMessage::loadMultiple()), 'SMS message saved.');
    $this->assertEquals(1, count($return));
    $this->assertTrue($return[0] instanceof SmsMessageInterface);
  }

  /**
   * Test message is not queued because no gateway is set.
   *
   * @covers ::send
   */
  public function testQueueNoFallbackGateway() {
    $this->smsProvider->setDefaultGateway(NULL);
    $this->setExpectedException(\Drupal\sms\Exception\RecipientRouteException::class);
    $message = $this->createSmsMessage()
      ->addRecipients($this->randomPhoneNumbers());
    $this->smsProvider->queue($message);
  }

  /**
   * Test message is sent immediately.
   */
  public function testSkipQueue() {
    $this->gateway
      ->setSkipQueue(TRUE)
      ->save();
    $sms_message = $this->createSmsMessage()
      ->addRecipients($this->randomPhoneNumbers());
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
    $this->assertEquals(Direction::INCOMING, $sms_message_loaded->getDirection());
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
    $this->assertEquals(Direction::OUTGOING, $sms_message_loaded->getDirection());
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
   * Test an exception is thrown if a message has no recipients
   */
  public function testNoRecipients() {
    $this->setExpectedException(\Drupal\sms\Exception\RecipientRouteException::class, 'There are no recipients.');
    $sms_message = SmsMessage::create()
      ->setDirection(Direction::OUTGOING)
      ->setMessage($this->randomString());
    $this->smsProvider->send($sms_message);
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
    $return = $this->smsProvider->queue($sms_message);

    $this->assertEquals(2, count(SmsMessage::loadMultiple()), 'One SMS message has been split into two.');
    $this->assertEquals(2, count($return), 'Provider queue method returned two messages.');
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
   * Ensure hook_sms_incoming_preprocess is fired.
   */
  public function testIncomingHookPreprocess() {
    $this->gateway
      ->setSkipQueue(TRUE)
      ->save();

    $sms_message = $this->createSmsMessage()
      ->setGateway($this->gateway)
      ->setDirection(Direction::INCOMING)
      ->addRecipients($this->randomPhoneNumbers());

    $this->smsProvider->queue($sms_message);
    $this->assertTrue(\Drupal::state()->get('sms_test_sms_incoming_preprocess'));
  }

  /**
   * Ensure hook_sms_incoming_postprocess is fired.
   */
  public function testIncomingHookPostprocess() {
    $this->gateway
      ->setSkipQueue(TRUE)
      ->save();

    $sms_message = $this->createSmsMessage()
      ->setGateway($this->gateway)
      ->setDirection(Direction::INCOMING)
      ->addRecipients($this->randomPhoneNumbers());

    $this->smsProvider->queue($sms_message);
    $this->assertTrue(\Drupal::state()->get('sms_test_sms_incoming_postprocess'));
  }

  /**
   * Ensure SMS message is processed by the gateway.
   */
  public function testIncomingHookProcess() {
    $this->gateway
      ->setSkipQueue(TRUE)
      ->save();

    $sms_message = $this->createSmsMessage()
      ->setGateway($this->gateway)
      ->setDirection(Direction::INCOMING)
      ->addRecipients($this->randomPhoneNumbers());

    $this->smsProvider->queue($sms_message);
    $this->assertTrue(\Drupal::state()->get('sms_test_gateway.memory.incoming_hook_temporary'));
  }

  /**
   * Test get default gateway.
   */
  public function testGetDefaultGateway() {
    $gateway = $this->createMemoryGateway();
    $this->config('sms.settings')
      ->set('default_gateway', $gateway->id())
      ->save();
    $this->assertEquals($gateway->id(), $this->smsProvider->getDefaultGateway()->id());
  }

  /**
   * Test get default gateway not set.
   */
  public function testGetDefaultGatewayNotSet() {
    $this->config('sms.settings')
      ->set('default_gateway', NULL)
      ->save();
    $this->assertNull($this->smsProvider->getDefaultGateway());
  }

  /**
   * Test set default gateway.
   */
  public function testSetDefaultGateway() {
    $gateway = $this->createMemoryGateway();
    $this->smsProvider->setDefaultGateway($gateway);
    $this->assertEquals($gateway->id(), $this->config('sms.settings')->get('default_gateway'));
  }

  /**
   * Test unset default gateway.
   */
  public function testSetDefaultGatewayToNull() {
    $this->smsProvider->setDefaultGateway(NULL);
    $this->assertNull($this->config('sms.settings')->get('default_gateway'));
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
      ->setDirection(Direction::OUTGOING)
      ->setMessage($this->randomString());
  }

}
