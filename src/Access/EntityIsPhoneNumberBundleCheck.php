<?php

/**
 * @file
 * Contains \Drupal\sms\Access\EntityIsPhoneNumberBundleCheck.
 */

namespace Drupal\sms\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\Core\Entity\EntityInterface;

/**
 * Checks that an entity is phone number enabled
 */
class EntityIsPhoneNumberBundleCheck implements AccessInterface {

  /**
   * Checks that an entity is an event type.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $entity_type_id = $route->getRequirement('_entity_is_phone_number_bundle');

    $parameters = $route_match->getParameters();
    if ($parameters->has($entity_type_id)) {
      $entity = $parameters->get($entity_type_id);
      if ($entity instanceof EntityInterface) {
        $config = \Drupal::config('sms.phone.' . $entity->getEntityTypeId() . '.' . $entity->bundle());
        // @todo: check if this entity has an active confirmation code.
        return AccessResult::allowedIf($config->get());
      }
    }

    return AccessResult::neutral();
  }

}
