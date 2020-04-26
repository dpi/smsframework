<?php

namespace Drupal\Tests\sms\Kernel;

use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Direction;

/**
 * Tests SMS Framework provider service.
 *
 * @group SMS Framework
 */
class SmsFrameworkRecipientGatewayEventTest extends SmsFrameworkKernelBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'sms', 'sms_test', 'sms_test_gateway', 'field', 'telephone',
    'dynamic_entity_reference',
  ];

  /**
   * The default SMS provider.
   *
   * @var \Drupal\sms\Provider\SmsProviderInterface
   */
  protected $smsProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('sms');
    $this->smsProvider = $this->container->get('sms.provider');
    $this->setFallbackGateway(NULL);
  }

  /**
   * Test gateways are applied from the test event subscriber.
   *
   * @see \Drupal\sms_test\EventSubscriber\SmsTestEventSubscriber
   */
  public function testGatewayEventSubscriber() {
    $gateway_200 = $this->createMemoryGateway(['id' => 'test_gateway_200']);
    $gateway_200
      ->setSkipQueue(TRUE)
      ->save();
    $gateway_400 = $this->createMemoryGateway(['id' => 'test_gateway_400']);
    $gateway_400
      ->setSkipQueue(TRUE)
      ->save();

    \Drupal::state()->set('sms_test_event_subscriber__test_gateway_200', TRUE);
    \Drupal::state()->set('sms_test_event_subscriber__test_gateway_400', TRUE);

    $sms_message = SmsMessage::create()
      ->setDirection(Direction::OUTGOING)
      ->setMessage($this->randomString())
      ->addRecipients($this->randomPhoneNumbers());

    $sms_messages = $this->smsProvider->queue($sms_message);
    $this->assertEquals(1, count($sms_messages), 'One message dispatched.');
    $this->assertEquals('test_gateway_400', $sms_messages[0]->getGateway()->id());

    $this->assertEquals(0, count($this->getTestMessages($gateway_200)), 'Message not sent through gateway_200');
    $this->assertEquals(1, count($this->getTestMessages($gateway_400)), 'Message sent through gateway_400');
  }

}
