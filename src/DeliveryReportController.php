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
   * The gateway manager.
   *
   * @var \Drupal\sms\Gateway\GatewayManagerInterface
   */
  protected $gatewayManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Creates an new delivery report controller.
   *
   * @param \Drupal\sms\Gateway\GatewayManagerInterface $gateway_manager
   *   The gateway manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(GatewayManagerInterface $gateway_manager, RequestStack $request_stack) {
    $this->gatewayManager = $gateway_manager;
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
    $acknowledgement = $this->gatewayManager->getGateway($gateway_id)->deliveryReport($this->requestStack->getCurrentRequest());
    return new Response($acknowledgement);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.sms_gateway'),
      $container->get('request_stack')
    );
  }

}
