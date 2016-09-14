<?php

namespace Drupal\sms\Routing\Enhancer;

use Drupal\Core\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\sms\Entity\SmsGateway;

/**
 * Route enhancer for SMS Framework.
 */
class SmsRouteEnhancer implements RouteEnhancerInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $route->hasDefault('_sms_gateway_push_endpoint');
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    $gateway_id = $defaults['_sms_gateway_push_endpoint'];
    $defaults['sms_gateway'] = SmsGateway::load($gateway_id);
    return $defaults;
  }

}
