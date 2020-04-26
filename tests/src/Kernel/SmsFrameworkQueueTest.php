<?php

namespace Drupal\Tests\sms\Kernel;

use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Direction;

/**
 * Tests behaviour of SMS Framework message queue.
 *
 * @group SMS Framework
 */
class SmsFrameworkQueueTest extends SmsFrameworkKernelBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'sms', 'sms_test_gateway', 'field', 'telephone', 'dynamic_entity_reference',
  ];

  /**
   * The SMS provider.
   *
   * @var \Drupal\sms\Provider\SmsProviderInterface
   */
  protected $smsProvider;

  /**
   * The SMS queue processor.
   *
   * @var \Drupal\sms\Provider\SmsQueueProcessorInterface
   */
  protected $smsQueueProcessor;

  /**
   * A memory gateway.
   *
   * @var \Drupal\sms\Entity\SmsGatewayInterface
   */
  protected $gateway;

  /**
   * The cron service.
   *
   * @var \Drupal\Core\Cron
   */
  protected $cronService;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('sms');
    $this->installEntitySchema('sms_result');
    $this->installEntitySchema('sms_report');

    $this->gateway = $this->createMemoryGateway();
    $this->smsProvider = $this->container->get('sms.provider');
    $this->setFallbackGateway($this->gateway);
    $this->smsQueueProcessor = $this->container->get('sms.queue');
    $this->cronService = $this->container->get('cron');
  }

  /**
   * Tests unqueued unprocessed messages are added to the Drupal queue system.
   */
  public function testProcessUnqueued() {
    $sms_message = $this->createSmsMessage();

    $result = $this->smsProvider->queue($sms_message);
    $id = $result[0]->id();

    // Call processUnqueued manually so cron does not send the message with
    // queue workers.
    $this->smsQueueProcessor->processUnqueued();
    $sms_message_saved = SmsMessage::load($id);

    $this->assertTrue($sms_message_saved->isQueued(), 'SMS message is queued.');
    $this->assertEquals(1, \Drupal::queue('sms.messages')->numberOfItems(), 'SMS message processor queue item created.');
  }

  /**
   * Test message is queued and received on cron run.
   */
  public function testQueueIncoming() {
    $sms_message = $this->createSmsMessage()
      ->setDirection(Direction::INCOMING)
      ->addRecipients($this->randomPhoneNumbers())
      ->setGateway($this->gateway);
    $sms_message->setResult($this->createMessageResult($sms_message));

    $this->smsProvider->queue($sms_message);
    $this->assertEquals(0, count($this->getTestMessages($this->gateway)), 'Message not received yet.');

    $this->cronService->run();
    $this->assertEquals($sms_message->getMessage(), sms_test_gateway_get_incoming()['message'], 'Message was received.');
  }

  /**
   * Test message is queued and sent on cron run.
   */
  public function testQueueOutgoing() {
    $sms_message = $this->createSmsMessage()
      ->setDirection(Direction::OUTGOING);
    $this->smsProvider->queue($sms_message);
    $this->assertEquals(0, count($this->getTestMessages($this->gateway)), 'Message not sent yet.');

    $this->cronService->run();
    $this->assertEquals(1, count($this->getTestMessages($this->gateway)), 'Message was sent.');
  }

  /**
   * Test message is delayed.
   */
  public function testQueueDelayed() {
    $sms_message = $this->createSmsMessage()
      ->setSendTime(REQUEST_TIME + 9999);

    $this->smsProvider->queue($sms_message);

    $this->cronService->run();
    $this->assertEquals(0, count($this->getTestMessages($this->gateway)), 'Message not sent yet.');
  }

  /**
   * Test message is not delayed for schedule aware gateways..
   */
  public function testQueueNotDelayedScheduleAware() {
    $gateway = $this->createMemoryGateway(['plugin' => 'memory_schedule_aware']);

    $sms_message = $this->createSmsMessage()
      ->setSendTime(REQUEST_TIME + 9999)
      ->setGateway($gateway);

    $this->smsProvider->queue($sms_message);

    $this->cronService->run();
    $this->assertEquals(1, count($this->getTestMessages($gateway)), 'Message sent.');
  }

  /**
   * Test retention is set to delete messages immediately after transmission.
   *
   * Tests \Drupal\sms\Plugin\QueueWorker\SmsProcessor.
   */
  public function testRetentionImmediateDelete() {
    $this->gateway
      ->setRetentionDuration(Direction::OUTGOING, 0)
      ->save();

    $sms_message = $this->createSmsMessage();
    $this->smsProvider->queue($sms_message);

    $this->cronService->run();
    $this->assertEquals(1, count($this->getTestMessages($this->gateway)), 'One message was sent.');
    $this->assertEquals(0, count(SmsMessage::loadMultiple()), 'There are no SMS entities in storage.');
  }

  /**
   * Test retention is set to keep messages after transmission.
   *
   * Tests \Drupal\sms\Plugin\QueueWorker\SmsProcessor.
   */
  public function testRetentionPersist() {
    $this->gateway
      ->setRetentionDuration(Direction::OUTGOING, 9999)
      ->save();

    $sms_message = $this->createSmsMessage();
    $this->smsProvider->queue($sms_message);

    $this->cronService->run();
    $sms_messages = SmsMessage::loadMultiple();
    $sms_message_new = reset($sms_messages);

    $this->assertEquals(1, count($this->getTestMessages($this->gateway)), 'One message was sent.');
    $this->assertEquals(1, count($sms_messages), 'There are SMS entities in storage.');
    $this->assertEquals(REQUEST_TIME, $sms_message_new->getProcessedTime());
    $this->assertEquals(FALSE, $sms_message_new->isQueued());
  }

  /**
   * Test retention is set to keep messages forever.
   */
  public function testRetentionUnlimited() {
    $this->gateway
      ->setRetentionDuration(Direction::OUTGOING, -1)
      ->save();

    $this->createSmsMessage()
      ->setGateway($this->gateway)
      ->setQueued(FALSE)
      ->setProcessedTime(1)
      ->save();

    // Garbage collect.
    $this->cronService->run();

    $this->assertEquals(1, count(SmsMessage::loadMultiple()), 'There are SMS entities in storage.');
  }

  /**
   * Create a SMS message entity for testing.
   *
   * @param array $values
   *   An mixed array of values to pass when creating the SMS message entity.
   *
   * @return \Drupal\sms\Entity\SmsMessageInterface
   *   A SMS message entity for testing.
   */
  protected function createSmsMessage(array $values = []) {
    return SmsMessage::create($values)
      ->setDirection(Direction::OUTGOING)
      ->setMessage($this->randomString())
      ->addRecipients($this->randomPhoneNumbers(1));
  }

}
