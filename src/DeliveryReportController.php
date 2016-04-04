<?php

/**
 * @file
 * Contains \Drupal\sms\DeliveryReportController
 */

namespace Drupal\sms;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\sms\Entity\SmsGatewayInterface;
use Drupal\sms\Provider\SmsProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides delivery reports acknowledgement and passes to the correct gateway.
 */
class DeliveryReportController implements ContainerInjectionInterface {

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
   * Creates an new delivery report controller.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\sms\Provider\SmsProviderInterface $sms_provider
   *   The SMS service provider.
   */
  public function __construct(RequestStack $request_stack, SmsProviderInterface $sms_provider) {
    $this->requestStack = $request_stack;
    $this->smsProvider = $sms_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('sms_provider')
    );
  }

  /**
   * Acknowledges delivery reports and passes them to the correct gateway.
   *
   * @param \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway
   *   The ID of the SMS Gateway that is to handle the delivery report. Will be
   *   upcasted.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function processDeliveryReport(SmsGatewayInterface $sms_gateway) {
    return $this->smsProvider->processDeliveryReport($this->requestStack->getCurrentRequest(), $sms_gateway);
  }

}
