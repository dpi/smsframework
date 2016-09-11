<?php

namespace Drupal\Tests\sms\Kernel;

use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\Message\SmsMessage;
use Drupal\sms\Message\SmsMessageResultInterface;
use Drupal\sms\Direction;

/**
 * Tests sending SMS messages.
 *
 * @group SMS Framework
 */
class SmsFrameworkSmsSendTest extends SmsFrameworkKernelBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'sms', 'sms_test_gateway', 'telephone', 'dynamic_entity_reference',
  ];

  /**
   * The default SMS provider service.
   *
   * @var \Drupal\sms\Provider\SmsProviderInterface
   */
  protected $defaultSmsProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->defaultSmsProvider = $this->container->get('sms.provider');
  }

  /**
   * Test that gateways are correctly discovered from installation.
   *
   * See `sms.gateway.log.yml`.
   */
  public function testGatewayInstall() {
    $this->assertEquals(
      ['log'],
      array_keys(SmsGateway::loadMultiple())
    );
  }

  /**
   * Test default gateway change in same request.
   */
  public function testDefaultGatewayChange() {
    $gateways = [];
    $message_counts = [];
    for ($a = 0; $a < 3; $a++) {
      $gateways[$a] = $this->createMemoryGateway(['skip_queue' => TRUE]);
      $message_counts[$a] = 0;
    }

    $message_counts = [0, 0, 0];
    for ($a = 0; $a < 3; $a++) {
      foreach ($gateways as $i => &$gateway) {
        $this->setFallbackGateway($gateway);

        $sms_message = (new SmsMessage())
          ->addRecipients($this->randomPhoneNumbers(1))
          ->setMessage($this->randomString())
          ->setDirection(Direction::OUTGOING);
        $this->defaultSmsProvider->queue($sms_message);

        $message_counts[$i]++;
        foreach ($gateways as $k => $gateway2) {
          $this->assertEquals($message_counts[$k], count($this->getTestMessages($gateway2)));
        }
      }
    }
  }

  /**
   * Tests overriding default gateway with message option.
   */
  public function testSmsSendSpecified() {
    $test_gateway1 = $this->createMemoryGateway(['skip_queue' => TRUE]);
    $test_gateway2 = $this->createMemoryGateway(['skip_queue' => TRUE]);
    $this->setFallbackGateway($test_gateway1);

    $sms_message = (new SmsMessage())
      ->addRecipients($this->randomPhoneNumbers(1))
      ->setMessage($this->randomString())
      ->setGateway($test_gateway2);

    $sms_messages = $this->defaultSmsProvider->send($sms_message);
    $this->assertTrue($sms_messages[0]->getResult() instanceof SmsMessageResultInterface, 'Message successfully sent.');
    $this->assertEquals(0, count($this->getTestMessages($test_gateway1)), 'Message not sent to the default gateway.');
    $this->assertEquals(1, count($this->getTestMessages($test_gateway2)), 'Message sent to the specified gateway.');
  }

}
