<?php

/**
 * @file
 * Contains \Drupal\sms\Entity\SmsGateway.
 */

namespace Drupal\sms\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\sms\Plugin\SmsGatewayPluginCollection;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\sms\Direction;

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
   * Whether messages sent to this gateway should be sent immediately.
   *
   * @var boolean
   */
  protected $skip_queue;

  /**
   * How many seconds to hold messages after they are received.
   *
   * @var integer
   */
  protected $retention_duration_incoming;

  /**
   * How many seconds to hold messages after they are sent.
   *
   * @var integer
   */
  protected $retention_duration_outgoing;

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

  /**
   * {@inheritdoc}
   */
  public function getSkipQueue() {
    return !empty($this->skip_queue);
  }

  /**
   * {@inheritdoc}
   */
  public function setSkipQueue($skip_queue) {
    $this->skip_queue = (boolean)$skip_queue;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRetentionDuration($direction) {
    switch ($direction) {
      case Direction::INCOMING:
        return (int)$this->retention_duration_incoming;
      case Direction::OUTGOING:
        return (int)$this->retention_duration_outgoing;
      default:
        throw new \Exception('%s is not a valid direction.', $direction);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setRetentionDuration($direction, $retention_duration) {
    switch ($direction) {
      case Direction::INCOMING:
        $this->retention_duration_incoming = $retention_duration;
        break;
      case Direction::OUTGOING:
        $this->retention_duration_outgoing = $retention_duration;
        break;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxRecipientsOutgoing() {
    $definition = $this->getPlugin()
      ->getPluginDefinition();
    return isset($definition['outgoing_message_max_recipients']) ? (int)$definition['outgoing_message_max_recipients'] : 1;
  }

  /**
   * {@inheritdoc}
   */
  public function isScheduleAware() {
    $definition = $this->getPlugin()
      ->getPluginDefinition();
    return !empty($definition['schedule_aware']);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsCreditBalanceQuery() {
    $definition = $this->getPlugin()
      ->getPluginDefinition();
    return isset($definition['credit_balance_available']) ? (boolean) $definition['credit_balance_available'] : FALSE;
  }

}
