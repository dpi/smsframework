<?php

declare(strict_types=1);

namespace Drupal\sms;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\sms\Entity\SmsGatewayInterface;
use Drupal\sms\Provider\SmsProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides delivery reports acknowledgement and passes to the correct gateway.
 */
class DeliveryReportController implements ContainerInjectionInterface {

  /**
   * Creates a new delivery report controller.
   *
   * @param \Drupal\sms\Provider\SmsProviderInterface $smsProvider
   *   The SMS service provider.
   */
  final public function __construct(
    private SmsProviderInterface $smsProvider,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  final public static function create(ContainerInterface $container): static {
    return new static(
      $container->get(SmsProviderInterface::class),
    );
  }

  /**
   * Acknowledges delivery reports and passes them to the correct gateway.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway
   *   The gateway which is handling the delivery report.
   */
  public function processDeliveryReport(Request $request, SmsGatewayInterface $sms_gateway): Response {
    return $this->smsProvider->processDeliveryReport($request, $sms_gateway);
  }

}
