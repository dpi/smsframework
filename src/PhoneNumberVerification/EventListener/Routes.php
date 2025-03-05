<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumberVerification\EventListener;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

final class Routes extends RouteSubscriberBase {

  public function __construct(
    private bool $enabled,
    private string $path,
  ) {
  }

  protected function alterRoutes(RouteCollection $collection): void {
    // Alters the route as defined in sms.routing.yml.
    if ($this->enabled) {
      $route = $collection->get('sms.phone.verify');
      $route?->setPath($this->path);
    }
    else {
      $collection->remove('sms.phone.verify');
    }
  }

}
