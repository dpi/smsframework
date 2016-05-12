<?php

namespace Drupal\sms\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\sms\Entity\SmsGatewayInterface;

/**
 * Event fired to determine valid gateways for a recipient.
 */
class RecipientGatewayEvent extends Event {

  /**
   * The recipient.
   *
   * @var \Drupal\sms\Message\SmsMessageInterface[]
   */
  protected $recipient;

  /**
   * The gateways.
   */
  protected $gateways = [];

  /**
   * Constructs the object.
   *
   * @param string $recipient
   *   The recipient phone number.
   */
  public function __construct($recipient) {
    $this->setRecipient($recipient);
  }

  /**
   * @return string
   */
  public function getRecipient() {
    return $this->recipient;
  }

  /**
   * @param string $recipient
   *
   * @return $this
   */
  public function setRecipient($recipient) {
    $this->recipient = $recipient;
    return $this;
  }

  /**
   * Get gateways.
   *
   * @return array
   *   An array of gateway + priority pairs.
   */
  public function getGateways() {
    return $this->gateways;
  }

  /**
   * Return gateways ordered by priority from highest to lowest.
   *
   * @return \Drupal\sms\Entity\SmsGatewayInterface[]
   */
  public function getGatewaysSorted() {
    uasort($this->gateways, function($a, $b) {
      list(, $priority_a) = $a;
      list(, $priority_b) = $b;
      if ($priority_a == $priority_b) {
        return 0;
      }
      return ($priority_a > $priority_b) ? -1 : 1;
    });

    $gateways = [];
    foreach ($this->gateways as $pair) {
      list($gateway, ) = $pair;
      $gateways[] = $gateway;
    }

    return $gateways;
  }

  /**
   * @param \Drupal\sms\Entity\SmsGatewayInterface $gateway
   * @param int $priority
   *
   * @return $this
   */
  public function addGateway(SmsGatewayInterface $gateway, $priority = 0) {
    $this->gateways[] = [$gateway, $priority];
    return $this;
  }

  /**
   * @param $gateway_id
   *   A gateway plugin ID.
   *
   * @param integer|NULL $priority
   *   The priority of the gateway to remove, or NULL to remove all gateways
   *   with the identifier.
   *
   * @return $this
   */
  public function removeGateway($gateway_id, $priority = NULL) {
    foreach ($this->gateways as $k => $pair) {
      list($gateway, $gateway_priority) = $pair;
      if ($gateway_id == $gateway->id()) {
        if (!isset($priority) || ($priority == $gateway_priority)) {
          unset($this->gateways[$k]);
        }
      }
    }
    return $this;
  }

}
