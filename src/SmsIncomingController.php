<?php

declare(strict_types=1);

namespace Drupal\sms;

use Drupal\Core\Controller\ControllerBase;
use Drupal\sms\Entity\SmsGatewayInterface;
use Drupal\sms\Provider\SmsProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;

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

  final public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('http_kernel.controller.argument_resolver'),
      $container->get(SmsProviderInterface::class),
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
   */
  public function processIncoming(Request $request, SmsGatewayInterface $sms_gateway): mixed {
    $controller = [$sms_gateway->getPlugin(), 'processIncoming'];
    $arguments = $this->argumentResolver
      ->getArguments($request, $controller);

    /** @var \Drupal\sms\SmsProcessingResponse $response */
    $response = \call_user_func_array($controller, $arguments);

    foreach ($response->getMessages() as $message) {
      $this->smsProvider->queue($message);
    }

    return $response->getResponse();
  }

}
