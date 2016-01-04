<?php

/**
 * @file
 * Contains \Drupal\sms\DeliveryReportController
 */

namespace Drupal\sms;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\sms\Entity\SmsGateway;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sms\Plugin\SmsGatewayPluginManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides delivery reports acknowledgement and passes to the correct gateway.
 */
class DeliveryReportController implements ContainerInjectionInterface {

  /**
   * The gateway manager.
   *
   * @var \Drupal\sms\Plugin\SmsGatewayPluginManagerInterface
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
   * @param \Drupal\sms\Plugin\SmsGatewayPluginManagerInterface $gateway_manager
   *   The gateway manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(SmsGatewayPluginManagerInterface $gateway_manager, RequestStack $request_stack) {
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
    /** @var \Drupal\sms\Entity\SmsGateway $sms_gateway */
    $sms_gateway = SmsGateway::load($gateway_id);
    $acknowledgement = $sms_gateway
      ->getPlugin()
      ->deliveryReport($this->requestStack->getCurrentRequest());
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
