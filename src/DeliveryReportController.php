<?php

/**
 * @file
 * Contains \Drupal\sms\DeliveryReportController
 */

namespace Drupal\sms;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sms\Gateway\GatewayManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides delivery reports acknowledgement and passes to the correct gateway.
 */
class DeliveryReportController implements ContainerInjectionInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Creates an new delivery report controller.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * Acknowledges delivery reports and passes them to the correct gateway.
   *
   * @param string $gateway_id
   *   The gateway id of the gateway that is to handle the delivery report.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function acknowledgeDelivery($gateway_id) {
    $gateway = sms_gateways('gateway', $gateway_id);
    if (is_callable($gateway['delivery report'])) {
      $acknowledgement = $gateway['delivery report']($this->requestStack->getCurrentRequest());
      return new Response($acknowledgement);
    }
    // Because we don't want a 500 going to the SMS gateway server we return an
    // empty response.
    return new Response('');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

}
