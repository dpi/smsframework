<?php

namespace Drupal\sms\Access;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\sms\Entity\SmsGatewayInterface;

/**
 * Checks if gateway supports pushed reports.
 */
class SupportsPushedReportsAccessCheck implements AccessInterface {

  /**
   * Checks if the gateway supports pushed reports.
   */
  public function access(Request $request) {
    // SmsGatewayInterface $sms_gateway) {
    if ($request->attributes->has('sms_gateway')) {
      /** @var \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway */
      $sms_gateway = $request->attributes->get('sms_gateway');
      return AccessResult::allowedIf($sms_gateway->supportsReportsPush())
        ->addCacheContexts(['route'])
        ->addCacheContexts($sms_gateway->getCacheContexts());
    }
    return AccessResult::neutral();
  }

}
