<?php

/**
 * @file
 * Contains \Drupal\Tests\sms\Kernel\SmsFrameworkSmsSendTest.
 */

namespace Drupal\Tests\sms\Kernel;

use Drupal\sms\Entity\SmsGateway;

/**
 * Tests sending SMS messages.
 *
 * @group SMS Framework
 */
class SmsFrameworkSmsSendTest extends SmsFrameworkKernelBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['sms', 'sms_test_gateway'];

  /**
   * The default SMS provider service.
   *
   * @var \Drupal\sms\Provider\DefaultSmsProvider
   */
  protected $defaultSmsProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->defaultSmsProvider = $this->container->get('sms_provider.default');
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
      $gateways[$a] = $this->createMemoryGateway();
      $message_counts[$a] = 0;
    }

    $message_counts = [0, 0, 0];
    for ($a = 0; $a < 3; $a++) {
      foreach ($gateways as $i => &$gateway) {
        $this->defaultSmsProvider->setDefaultGateway($gateway);
        sms_send('+123123123', $this->randomString());
        $message_counts[$i]++;
        foreach ($gateways as $k => $gateway2) {
          $this->assertEquals($message_counts[$k], count($this->getTestMessages($gateway2)));
        }
      }
    }
  }

  /**
   * Tests the sending of messages.
   *
   * Tests SMS message 'gateway' option.
   */
  public function testSmsSendSpecified() {
    $test_gateway1 = $this->createMemoryGateway();
    $test_gateway2 = $this->createMemoryGateway();
    $this->defaultSmsProvider->setDefaultGateway($test_gateway1);

    // Test message goes to default gateway.
    $message = $this->randomString();
    $number = '+123123123';
    $options['sender'] = 'Sender';

    $result = sms_send($number, $message, $options);
    $this->assertTrue($result, 'Message successfully sent.');

    $this->assertEquals(1, count($this->getTestMessages($test_gateway1)), 'Message sent to default gateway.');
    $this->assertEquals(0, count($this->getTestMessages($test_gateway2)), 'Message not sent to extra gateway.');

    // Test message goes to specified gateway.
    $options['gateway'] = $test_gateway2->id();
    $result = sms_send($number, $message, $options);
    $this->assertTrue($result, 'Message successfully sent.');
    $this->assertEquals(1, count($this->getTestMessages($test_gateway2)), 'Message sent to the specified gateway.');
    $this->assertEquals(1, count($this->getTestMessages($test_gateway1)), 'Message not sent to the default gateway.');
  }

}
