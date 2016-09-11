<?php

namespace Drupal\Tests\sms\Unit\Event;

use Drupal\Tests\UnitTestCase;
use Drupal\sms\Tests\SmsFrameworkTestTrait;
use Drupal\sms\Event\RecipientGatewayEvent;
use Drupal\sms\Entity\SmsGatewayInterface;

/**
 * Unit Tests for SmsMessage.
 *
 * @group SMS Framework
 * @coversDefaultClass \Drupal\sms\Event\RecipientGatewayEvent
 */
class RecipientGatewayEventTest extends UnitTestCase {

  use SmsFrameworkTestTrait;

  /**
   * Tests gateway sort.
   *
   * @covers ::getGatewaysSorted
   */
  public function testSortFunction() {
    $number = $this->randomPhoneNumbers()[0];
    $event = $this->createEvent($number);

    $gateway = $this->getMock(SmsGatewayInterface::class);
    $gateway
      ->expects($this->any())
      ->method('id')
      ->willReturn('gateway_first');
    $event->addGateway($gateway, 100);

    $gateway = $this->getMock(SmsGatewayInterface::class);
    $gateway
      ->expects($this->any())
      ->method('id')
      ->willReturn('gateway_second');
    $event->addGateway($gateway, 200);

    $sorted = $event->getGatewaysSorted();
    $this->assertEquals(2, count($sorted));
    $this->assertEquals('gateway_second', $sorted[0]->id());
    $this->assertEquals('gateway_first', $sorted[1]->id());
  }

  /**
   * Tests recipient constructor.
   *
   * @covers ::getRecipient
   */
  public function testRecipientConstructor() {
    $number = $this->randomPhoneNumbers()[0];
    $event = $this->createEvent($number);
    $this->assertEquals($number, $event->getRecipient(), 'Constructor recipient is set');
  }

  /**
   * Tests recipient methods.
   *
   * @covers ::getRecipient
   * @covers ::setRecipient
   */
  public function testRecipient() {
    $event = $this->createEvent($this->randomPhoneNumbers()[0]);

    $number = $this->randomPhoneNumbers()[0];
    $event->setRecipient($number);
    $this->assertEquals($number, $event->getRecipient(), 'Recipient is set via setRecipient');
  }

  /**
   * Tests gateway add and getter.
   *
   * @covers ::addGateway
   * @covers ::getGateways
   */
  public function testGetGateways() {
    $event = $this->createEvent($this->randomPhoneNumbers()[0]);

    $gateway = $this->getMock(SmsGatewayInterface::class);
    $gateway
      ->expects($this->any())
      ->method('id')
      ->willReturn('gateway_1');
    $event->addGateway($gateway, 200);

    $gateway = $this->getMock(SmsGatewayInterface::class);
    $gateway
      ->expects($this->any())
      ->method('id')
      ->willReturn('gateway_2');
    $event->addGateway($gateway, 400);

    $gateways = $event->getGateways();
    $this->assertEquals('gateway_1', $gateways[0][0]->id());
    $this->assertEquals(200, $gateways[0][1]);
    $this->assertEquals('gateway_2', $gateways[1][0]->id());
    $this->assertEquals(400, $gateways[1][1]);
  }

  /**
   * Tests removing gateway with both ID and priority.
   *
   * @covers ::removeGateway
   */
  public function testGatewayRemove() {
    $event = $this->createEvent($this->randomPhoneNumbers()[0]);

    $gateway = $this->getMock(SmsGatewayInterface::class);
    $gateway
      ->expects($this->any())
      ->method('id')
      ->willReturn('gateway_1');
    $event->addGateway($gateway, 200);

    $gateway = $this->getMock(SmsGatewayInterface::class);
    $gateway
      ->expects($this->any())
      ->method('id')
      ->willReturn('gateway_1');
    $event->addGateway($gateway, 400);

    $gateway = $this->getMock(SmsGatewayInterface::class);
    $gateway
      ->expects($this->any())
      ->method('id')
      ->willReturn('gateway_2');
    $event->addGateway($gateway, 600);

    $this->assertEquals(3, count($event->getGateways()), 'There are three gateways.');

    $event->removeGateway('gateway_1', 400);
    $this->assertEquals(2, count($event->getGateways()), 'One gateways was removed.');
  }

  /**
   * Tests removing gateways with same identifier.
   *
   * @covers ::removeGateway
   */
  public function testGatewayRemoveAllSameId() {
    $event = $this->createEvent($this->randomPhoneNumbers()[0]);

    $gateway = $this->getMock(SmsGatewayInterface::class);
    $gateway
      ->expects($this->any())
      ->method('id')
      ->willReturn('gateway_1');
    $event->addGateway($gateway, 200);

    $gateway = $this->getMock(SmsGatewayInterface::class);
    $gateway
      ->expects($this->any())
      ->method('id')
      ->willReturn('gateway_1');
    $event->addGateway($gateway, 400);

    $gateway = $this->getMock(SmsGatewayInterface::class);
    $gateway
      ->expects($this->any())
      ->method('id')
      ->willReturn('gateway_2');
    $event->addGateway($gateway, 600);

    $this->assertEquals(3, count($event->getGateways()), 'There are three gateways.');

    $event->removeGateway('gateway_1');
    $this->assertEquals(1, count($event->getGateways()), 'Two gateways were removed.');
  }

  /**
   * Create a new event for testing.
   *
   * @param string $recipient
   *   The recipient phone number.
   *
   * @return \Drupal\sms\Event\RecipientGatewayEvent
   *   A new recipient gateway event instance.
   */
  public function createEvent($recipient) {
    return new RecipientGatewayEvent($recipient);
  }

}
