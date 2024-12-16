<?php

declare(strict_types=1);

namespace Drupal\sms;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\sms\Entity\SmsGatewayInterface;
use Drupal\sms\Provider\SmsProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides delivery reports acknowledgement and passes to the correct gateway.
 */
class DeliveryReportController implements ContainerInjectionInterface {

  /**
   * Creates a new delivery report controller.
   */
  final public function __construct(
    private SmsProviderInterface $smsProvider,
  ) {
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('sms.provider'),
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
  public function __invoke(Request $request, SmsGatewayInterface $sms_gateway): Response {
    return $this->smsProvider->processDeliveryReport($request, $sms_gateway);
  }

  public function processDeliveryReport(Request $request, SmsGatewayInterface $sms_gateway): Response {
    // phpcs:ignore Drupal.Semantics.UnsilencedDeprecation.UnsilencedDeprecation
    @\trigger_error(__METHOD__ . ' is deprecated. Use invoke instead.', E_USER_DEPRECATED);
    return $this($request, $sms_gateway);
  }

}
