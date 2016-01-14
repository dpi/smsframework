<?php

/**
 * @file
 * Contains \Drupal\sms\Plugin\Derivative\LocalTasks.
 */

namespace Drupal\sms\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides dynamic tasks for SMS Framework.
 */
class LocalTasks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a LocalTasks object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct(EntityManagerInterface $entity_manager, RouteProviderInterface $route_provider) {
    $this->entityManager = $entity_manager;
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager'),
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    $entity_types = [];
    foreach (\Drupal::configFactory()->listAll('sms.phone') as $config_name) {
      $config = \Drupal::config($config_name)->get();
      $entity_types[$config['entity_type']][$config['bundle']] = $config;
    }

    foreach (array_keys($entity_types) as $entity_type_id) {
      // Ensure entity type exists.
      if ($definition = $this->entityManager->getDefinition($entity_type_id)) {
        // Ensure route exists.
        $route_id = "sms.phone.$entity_type_id.number_verification";
        if ($this->routeProvider->getRouteByName($route_id)) {
          // Get cache tags for entity type.
          $cache_tags = $this->entityManager
            ->getDefinition($entity_type_id)
            ->getListCacheTags();

          // Get cache tags for all phone configs for this entity type.
          foreach ($entity_types[$entity_type_id] as $config) {
            $phone_config = $this->entityManager
              ->getStorage('phone_number_settings')
              ->load($config['id']);
            $cache_tags = Cache::mergeTags($cache_tags, $phone_config->getCacheTags());
          }

          $this->derivatives["sms.phone.$entity_type_id.number_verification"] = array(
            'title' => t('Phone number verification'),
            'base_route' => "entity.$entity_type_id.canonical",
            'route_name' => $route_id,
            'weight' => 10,
            'cache_tags' => $cache_tags,
          );
        }
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
