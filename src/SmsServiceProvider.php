<?php

declare(strict_types=1);

namespace Drupal\sms;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

/**
 * Service provider for SMS Framework.
 */
final class SmsServiceProvider implements ServiceProviderInterface {

  public function register(ContainerBuilder $container): void {
    $container
      ->addCompilerPass(new SmsCompilerPass());
  }

}
