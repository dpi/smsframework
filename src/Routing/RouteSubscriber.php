<?php

declare(strict_types=1);

namespace Drupal\sms\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\sms\DeliveryReportController;
use Drupal\sms\Form\VerifyPhoneNumberForm;
use Drupal\sms\SmsIncomingController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for SMS Framework routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Constructs a new SMS Framework RouteSubscriber.
   */
  public function __construct(
    private ConfigFactoryInterface $configFactory,
    private EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  protected function alterRoutes(RouteCollection $collection): void {
    $sms_settings = $this->configFactory->get('sms.settings');

    // Phone number verification.
    /** @var string|null $path_verify */
    $path_verify = $sms_settings->get('page.verify');
    // String length must include at least a slash + another character.
    if (\is_string($path_verify) && \strlen($path_verify) >= 2) {
      $collection->add('sms.phone.verify', new Route(
        $path_verify,
        [
          '_form' => VerifyPhoneNumberForm::class,
          '_title' => 'Verify a phone number',
        ],
        [
          '_permission' => 'sms verify phone number',
        ],
      ));
    }

    /** @var \Drupal\sms\Entity\SmsGatewayInterface $gateway */
    foreach ($this->entityTypeManager->getStorage('sms_gateway')->loadMultiple() as $id => $gateway) {
      if ($gateway->supportsReportsPush()) {
        $path = $gateway->getPushReportPath();
        if ($path !== NULL && \strlen($path) >= 2 && \str_starts_with($path, '/')) {
          $route = (new Route($path))
            ->setDefault('_controller', DeliveryReportController::class)
            ->setDefault('_sms_gateway_push_endpoint', $id)
            ->setRequirement('_sms_gateway_supports_pushed_reports', 'TRUE');
          $collection->add(\sprintf('sms.delivery_report.receive.%s', $id), $route);
        }
      }

      if ($gateway->autoCreateIncomingRoute()) {
        $path = $gateway->getPushIncomingPath();
        if ($path !== NULL && \strlen($path) >= 2 && \str_starts_with($path, '/')) {
          $parameters = [];
          $parameters['sms_gateway']['type'] = 'entity:sms_gateway';
          $route = (new Route($path))
            ->setDefault('sms_gateway', $id)
            ->setDefault('_controller', SmsIncomingController::class)
            ->setRequirement('_access', 'TRUE')
            ->setOption('parameters', $parameters)
            ->setMethods(['POST']);
          $collection->add(\sprintf('sms.incoming.receive.%s', $id), $route);
        }
      }
    }
  }

}
