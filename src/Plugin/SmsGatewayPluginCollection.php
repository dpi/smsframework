<?php

/**
 * @file
 * Contains \Drupal\sms\Plugin\SmsGatewayPluginCollection
 */

namespace Drupal\sms\Plugin;

use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Provides a container for lazily loading SMS Gateway plugins.
 */
class SmsGatewayPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\sms\Gateway\GatewayInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

}
