<?php

namespace Drupal\sms\Access;

use Symfony\Component\Routing\Route;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\sms\Entity\SmsGatewayInterface;

/**
 * Checks if gateway supports pushed reports.
 */
class SupportsPushedReportsAccessCheck implements AccessInterface {

  /**
   * Checks if the gateway supports pushed reports
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account, SmsGatewayInterface $sms_gateway) {
    return AccessResult::allowedIf($sms_gateway->supportsReportsPush())
      ->addCacheContexts(['route']);
  }

}
