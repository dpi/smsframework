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
use Drupal\sms\Event\SmsEvents;
use Drupal\sms\Message\SmsMessageResultInterface;

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
   * SMS message entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $smsStorage;

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
    $this->smsStorage = $this->container->get('entity_type.manager')
      ->getStorage('sms');
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

    $results = $this->smsProvider->send($message);

    $this->assertEquals(1, count($results), 'Return value contains 1 item.');
    $this->assertTrue($results[0] instanceof SmsMessageResultInterface, 'Return value is a SMS report.');
    $this->assertEquals(1, count($this->getTestMessages($this->gateway)));
  }

  /**
   * Test message is received.
   *
   * @covers ::incoming
   */
  public function testIncoming() {
    $message = $this->randomString();
    $sms_message = SmsMessage::create()
      ->setDirection(Direction::INCOMING)
      ->setMessage($message)
      ->addRecipients($this->randomPhoneNumbers());

    $results = $this->smsProvider->incoming($sms_message);

    $this->assertEquals($message, sms_test_gateway_get_incoming()['message'], 'Message was received.');
    $this->assertEquals(1, count($results), 'Return value contains 1 item.');
    $this->assertTrue($results[0] instanceof SmsMessageResultInterface, 'Return value is a SMS report.');
  }

  /**
   * Ensure no messages sent if no recipients.
   */
  public function testNoSendNoRecipients() {
    $sms_message = SmsMessage::create()
      ->setDirection(Direction::OUTGOING)
      ->setMessage($this->randomString());
    $this->setExpectedException(\Drupal\sms\Exception\RecipientRouteException::class, 'There are no recipients');
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
    $sms_message = new StandardSmsMessage();
    $sms_message
      ->addRecipients($this->randomPhoneNumbers())
      ->setMessage($this->randomString())
      ->setDirection(Direction::INCOMING);

    $sms_messages = $this->smsStorage
      ->loadByProperties(['direction' => Direction::INCOMING]);
    $this->assertEquals(0, count($sms_messages), 'There is zero SMS message in the incoming queue.');

    $this->smsProvider
      ->queue($sms_message);

    $sms_messages = $this->smsStorage
      ->loadByProperties(['direction' => Direction::INCOMING]);
    $this->assertEquals(1, count($sms_messages), 'There is one SMS message in the incoming queue.');

    $sms_message_loaded = reset($sms_messages);
    $this->assertEquals(Direction::INCOMING, $sms_message_loaded->getDirection());
  }

  /**
   * Test sending standard SMS object queue out.
   */
  public function testQueueOut() {
    $sms_message = new StandardSmsMessage();
    $sms_message
      ->addRecipients($this->randomPhoneNumbers())
      ->setMessage($this->randomString())
      ->setDirection(Direction::OUTGOING);

    $sms_messages = $this->smsStorage
      ->loadByProperties(['direction' => Direction::OUTGOING]);
    $this->assertEquals(0, count($sms_messages), 'There is zero SMS message in the outgoing queue.');

    $this->smsProvider->queue($sms_message);

    $sms_messages = $this->smsStorage
      ->loadByProperties(['direction' => Direction::OUTGOING]);
    $this->assertEquals(1, count($sms_messages), 'There is one SMS message in the outgoing queue.');

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
      ->setMessage($this->randomString())
      ->setDirection(Direction::OUTGOING);

    $this->smsProvider->queue($sms_message);
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
   * Ensure events are executed when a message added to the outgoing queue.
   */
  public function testEventsQueueOutgoing() {
    $sms_message = $this->createSmsMessage()
      ->setGateway($this->gateway)
      ->setDirection(Direction::OUTGOING)
      ->addRecipients($this->randomPhoneNumbers());

    $this->smsProvider->queue($sms_message);

    $expected = [
      SmsEvents::MESSAGE_PRE_PROCESS,
      SmsEvents::MESSAGE_QUEUE_PRE_PROCESS,
      SmsEvents::MESSAGE_QUEUE_POST_PROCESS,
    ];
    $execution_order = \Drupal::state()->get('sms_test_event_subscriber__execution_order', []);
    $this->assertEquals($expected, $execution_order);

    // Ensure SmsEvents::MESSAGE_PRE_PROCESS is not executed. See
    // '_skip_preprocess_event' option.
    $this->container->get('cron')->run();

    $expected[] = SmsEvents::MESSAGE_OUTGOING_PRE_PROCESS;
    $expected[] = SmsEvents::MESSAGE_OUTGOING_POST_PROCESS;
    $expected[] = SmsEvents::MESSAGE_POST_PROCESS;

    $execution_order = \Drupal::state()->get('sms_test_event_subscriber__execution_order', []);
    $this->assertEquals($expected, $execution_order);
  }

  /**
   * Ensure events are executed when a message added to the outgoing queue and
   * the gateway is set to skip queue.
   */
  public function testEventsQueueOutgoingSkipQueue() {
    $this->gateway
      ->setSkipQueue(TRUE)
      ->save();

    $sms_message = $this->createSmsMessage()
      ->setGateway($this->gateway)
      ->setDirection(Direction::OUTGOING)
      ->addRecipients($this->randomPhoneNumbers());

    $this->smsProvider->queue($sms_message);

    $expected = [
      SmsEvents::MESSAGE_PRE_PROCESS,
      SmsEvents::MESSAGE_QUEUE_PRE_PROCESS,
      SmsEvents::MESSAGE_OUTGOING_PRE_PROCESS,
      SmsEvents::MESSAGE_OUTGOING_POST_PROCESS,
      SmsEvents::MESSAGE_POST_PROCESS,
      SmsEvents::MESSAGE_QUEUE_POST_PROCESS,
    ];
    $execution_order = \Drupal::state()->get('sms_test_event_subscriber__execution_order', []);
    $this->assertEquals($expected, $execution_order);
  }

  /**
   * Ensure events are executed when a message added to the incoming queue.
   */
  public function testEventsQueueIncoming() {
    $sms_message = $this->createSmsMessage()
      ->setGateway($this->gateway)
      ->setDirection(Direction::INCOMING)
      ->addRecipients($this->randomPhoneNumbers());

    $this->smsProvider->queue($sms_message);

    $expected = [
      SmsEvents::MESSAGE_PRE_PROCESS,
      SmsEvents::MESSAGE_QUEUE_PRE_PROCESS,
      SmsEvents::MESSAGE_QUEUE_POST_PROCESS,
    ];
    $execution_order = \Drupal::state()->get('sms_test_event_subscriber__execution_order', []);
    $this->assertEquals($expected, $execution_order);

    // Ensure SmsEvents::MESSAGE_PRE_PROCESS is not executed. See
    // '_skip_preprocess_event' option.
    $this->container->get('cron')->run();

    $expected[] = SmsEvents::MESSAGE_INCOMING_PRE_PROCESS;
    $expected[] = SmsEvents::MESSAGE_INCOMING_POST_PROCESS;
    $expected[] = SmsEvents::MESSAGE_POST_PROCESS;

    $execution_order = \Drupal::state()->get('sms_test_event_subscriber__execution_order', []);
    $this->assertEquals($expected, $execution_order);
  }

  /**
   * Ensure events are executed when a message added to the incoming queue and
   * the gateway is set to skip queue.
   */
  public function testEventsQueueIncomingSkipQueue() {
    $this->gateway
      ->setSkipQueue(TRUE)
      ->save();

    $sms_message = $this->createSmsMessage()
      ->setGateway($this->gateway)
      ->setDirection(Direction::INCOMING)
      ->addRecipients($this->randomPhoneNumbers());

    $this->smsProvider->queue($sms_message);

    $expected = [
      SmsEvents::MESSAGE_PRE_PROCESS,
      SmsEvents::MESSAGE_QUEUE_PRE_PROCESS,
      SmsEvents::MESSAGE_INCOMING_PRE_PROCESS,
      SmsEvents::MESSAGE_INCOMING_POST_PROCESS,
      SmsEvents::MESSAGE_POST_PROCESS,
      SmsEvents::MESSAGE_QUEUE_POST_PROCESS,
    ];
    $execution_order = \Drupal::state()->get('sms_test_event_subscriber__execution_order', []);
    $this->assertEquals($expected, $execution_order);
  }

  /**
   * Ensure events are executed when a message is sent.
   */
  public function testEventsOutgoing() {
    $sms_message = $this->createSmsMessage()
      ->setGateway($this->gateway)
      ->setDirection(Direction::OUTGOING)
      ->addRecipients($this->randomPhoneNumbers());

    $this->smsProvider->send($sms_message);

    $expected = [
      SmsEvents::MESSAGE_PRE_PROCESS,
      SmsEvents::MESSAGE_OUTGOING_PRE_PROCESS,
      SmsEvents::MESSAGE_OUTGOING_POST_PROCESS,
      SmsEvents::MESSAGE_POST_PROCESS,
    ];
    $execution_order = \Drupal::state()->get('sms_test_event_subscriber__execution_order', []);
    $this->assertEquals($expected, $execution_order);
  }

  /**
   * Ensure events are executed when a message is received.
   */
  public function testEventsIncoming() {
    $sms_message = $this->createSmsMessage()
      ->setGateway($this->gateway)
      ->setDirection(Direction::INCOMING)
      ->addRecipients($this->randomPhoneNumbers());

    $this->smsProvider->incoming($sms_message);

    $expected = [
      SmsEvents::MESSAGE_PRE_PROCESS,
      SmsEvents::MESSAGE_INCOMING_PRE_PROCESS,
      SmsEvents::MESSAGE_INCOMING_POST_PROCESS,
      SmsEvents::MESSAGE_POST_PROCESS,
    ];
    $execution_order = \Drupal::state()->get('sms_test_event_subscriber__execution_order', []);
    $this->assertEquals($expected, $execution_order);
  }

  /**
   * Ensure SMS message is processed by the gateway.
   */
  public function testIncomingGatewayProcessed() {
    $this->gateway
      ->setSkipQueue(TRUE)
      ->save();

    $sms_message = $this->createSmsMessage()
      ->setGateway($this->gateway)
      ->setDirection(Direction::INCOMING)
      ->addRecipients($this->randomPhoneNumbers());

    $this->smsProvider->queue($sms_message);
    $this->assertTrue(\Drupal::state()->get('sms_test_gateway.memory.incoming'));
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
