<?php

/**
 * @file
 * Contains \Drupal\sms\DeliveryReportController
 */

namespace Drupal\sms;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\Provider\SmsProviderInterface;
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
   * The SMS Provider.
   *
   * @var \Drupal\sms\Provider\SmsProviderInterface
   */
  protected $smsProvider;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */

  /**
   * Creates an new delivery report controller.
   *
   * @param \Drupal\sms\Plugin\SmsGatewayPluginManagerInterface $gateway_manager
   *   The gateway manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(SmsGatewayPluginManagerInterface $gateway_manager, SmsProviderInterface $sms_provider, RequestStack $request_stack, ModuleHandlerInterface $module_handler) {
    $this->gatewayManager = $gateway_manager;
    $this->requestStack = $request_stack;
    $this->smsProvider = $sms_provider;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Acknowledges delivery reports and passes them to the correct gateway.
   *
   * @param string $gateway_name
   *   The ID of the SMS Gateway that is to handle the delivery report.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function acknowledgeDelivery($gateway_name) {
    // The response that will be sent back to the server API. The gateway plugin
    // can alter this response as needed.
    $response = new Response('');
    /** @var \Drupal\sms\Entity\SmsGateway $sms_gateway */
    $sms_gateway = SmsGateway::load($gateway_name);
    $reports = $sms_gateway
      ->getPlugin()
      ->parseDeliveryReports($this->requestStack->getCurrentRequest(), $response);
    // Invoke the delivery report and SMS receipt hooks.
    $this->moduleHandler->invokeAll('sms_delivery_report', [$reports, $response]);
    $this->smsProvider->receipt($reports, []);
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.sms_gateway'),
      $container->get('sms_provider'),
      $container->get('request_stack'),
      $container->get('module_handler')
    );
  }

}
