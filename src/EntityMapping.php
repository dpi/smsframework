<?php

declare(strict_types=1);

namespace Drupal\sms;

use Drupal\Core\Entity\EntityInterface;
use Drupal\sms\PhoneNumber\BundleConfiguration;

final class EntityMapping {

  /**
   * @var array<string, \Drupal\sms\PhoneNumber\BundleConfiguration>
   */
  private array $mapping = [];

  /**
   * @internal
   */
  public function __construct(
    string $mapping,
  ) {
    /** @var iterable<\Drupal\sms\PhoneNumber\BundleConfiguration> $configurations */
    $configurations = \unserialize($mapping);
    foreach ($configurations as $map) {
      $this->mapping[$map->entityTypeId . ':' . $map->bundle] = $map;
    }
  }

  /**
   * @internal
   */
  public function getMappingFor(EntityInterface $entity): ?BundleConfiguration {
    return $this->mapping[\sprintf('%s:%s', $entity->getEntityTypeId(), $entity->bundle())] ?? NULL;
  }

}
