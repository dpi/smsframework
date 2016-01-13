<?php

/**
 * @file
 * Contains \Drupal\sms\Routing\RouteSubscriber.
 */

namespace Drupal\sms\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\sms\EntityPhoneNumberProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Dynamic routes for SMS Framework.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Entity phone number provider.
   *
   * @var \Drupal\sms\EntityPhoneNumberProviderInterface
   */
  protected $entityPhoneNumber;


  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityPhoneNumberProviderInterface $entity_phone_number) {
    $this->entityManager = $entity_manager;
    $this->entityPhoneNumber = $entity_phone_number;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $entity_types = [];
    foreach (\Drupal::configFactory()->listAll('sms.phone') as $config_name) {
      $config = \Drupal::config($config_name)->get();
      $entity_types[$config['entity_type']][] = $config['bundle'];
    }

    foreach (array_keys($entity_types) as $entity_type_id) {
      // Ensure entity type exists.
      if ($definition = $this->entityManager->getDefinition($entity_type_id)) {
        if ($canonical_path = $definition->getLinkTemplate('canonical')) {
          $manage_requirements = [
            // @todo change permission.
//            '_permission' => 'access content',
            '_entity_access' => $entity_type_id . '.update',
            // Send entity type to get entity from upcaster.
            '_entity_is_phone_number_bundle' => $entity_type_id,
          ];
          $options = [];
          $options['parameters'][$entity_type_id]['type'] = 'entity:' . $entity_type_id;
          $route = new Route(
            $canonical_path . '/number_verification',
            [
//              '_controller' => '',
//              '_title' => 'Title',
            ],
            $manage_requirements,
            $options
          );
          $collection->add("sms.phone.$entity_type_id.number_verification", $route);
        }
      }
    }
  }

}
