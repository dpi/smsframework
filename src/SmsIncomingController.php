<?php

declare(strict_types=1);

namespace Drupal\sms;

use Drupal\Core\Controller\ControllerBase;
use Drupal\sms\Entity\SmsGatewayInterface;
use Drupal\sms\Provider\SmsProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Provides a controller for receiving incoming messages.
 */
class SmsIncomingController extends ControllerBase {

  /**
   * Creates an incoming route controller.
   */
  final public function __construct(
    protected ArgumentResolverInterface $argumentResolver,
    protected SmsProviderInterface $smsProvider,
  ) {
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get(ArgumentResolverInterface::class),
      $container->get('sms.provider'),
    );
  }

  /**
   * Receives incoming messages for a gateway.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   * @param \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway
   *   The gateway which is handling the the incoming request.
   *
   * @return mixed
   *   A response to return.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException
   *   When gateway doesn't have processIncoming method.
   */
  public function __invoke(Request $request, SmsGatewayInterface $sms_gateway): mixed {
    $controller = [$sms_gateway->getPlugin(), 'processIncoming'];
    if (\method_exists(...$controller) !== TRUE) {
      throw new ServiceUnavailableHttpException('Malformed gateway plugin');
    }

    /** @var callable(): \Drupal\sms\SmsProcessingResponse $callback */
    // @phpstan-ignore-next-line
    $callback = $controller(...);
    $arguments = $this->argumentResolver->getArguments($request, $callback);
    $response = $callback(...$arguments);

    foreach ($response->getMessages() as $message) {
      $this->smsProvider->queue($message);
    }

    return $response->getResponse();
  }

  public function processIncoming(Request $request, SmsGatewayInterface $sms_gateway): mixed {
    // phpcs:ignore Drupal.Semantics.UnsilencedDeprecation.UnsilencedDeprecation
    @\trigger_error(__METHOD__ . ' is deprecated. Use invoke instead.', E_USER_DEPRECATED);
    return $this($request, $sms_gateway);
  }

}
