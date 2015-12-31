<?php

/**
 * @file
 * Contains \Drupal\sms\Entity\SmsGateway.
 */

namespace Drupal\sms\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\sms\Plugin\SmsGatewayPluginCollection;
use Drupal\sms\SmsGatewayInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Defines storage for an SMS Gateway instance.
 *
 * @ConfigEntityType(
 *   id = "sms_gateway",
 *   label = @Translation("SMS Gateway"),
 *   config_prefix = "gateway",
 *   admin_permission = "administer smsframework",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   handlers = {
 *     "list_builder" = "\Drupal\sms\Lists\SmsGatewayListBuilder",
 *     "form" = {
 *       "add" = "Drupal\sms\Form\SmsGatewayForm",
 *       "default" = "Drupal\sms\Form\SmsGatewayForm",
 *       "edit" = "Drupal\sms\Form\SmsGatewayForm",
 *       "delete" = "Drupal\sms\Form\SmsGatewayDeleteForm",
 *     }
 *   },
 *   links = {
 *     "canonical" = "/admin/config/smsframework/gateways/{sms_gateway}",
 *     "edit-form" = "/admin/config/smsframework/gateways/{sms_gateway}",
 *     "delete-form" = "/admin/config/smsframework/gateways/{sms_gateway}/delete",
 *   },
 * )
 */
class SmsGateway extends ConfigEntityBase implements SmsGatewayInterface, EntityWithPluginCollectionInterface {

  /**
   * The ID of the SMS Gateway.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the SMS Gateway.
   *
   * @var string
   */
  protected $label;

  /**
   * The plugin instance settings.
   *
   * Access settings using:
   * @code
   *   $gateway->getPlugin()->getConfiguration();
   * @endcode
   *
   * @var array
   */
  protected $settings = [];

  /**
   * An SmsGateway plugin ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The plugin collection that holds the plugin for this entity.
   *
   * @var \Drupal\sms\Plugin\SmsGatewayPluginCollection
   */
  protected $pluginCollection;

  /**
   * Encapsulates the creation of the action's LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The action's plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection) {
      $this->pluginCollection = new SmsGatewayPluginCollection(
        \Drupal::service('plugin.manager.sms_gateway'),
        $this->plugin,
        $this->settings
      );
    }
    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['settings' => $this->getPluginCollection()];
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getPluginCollection()->get($this->plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin;
  }

}