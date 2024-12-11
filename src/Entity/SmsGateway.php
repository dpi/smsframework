<?php

declare(strict_types=1);

namespace Drupal\sms\Entity;

use Drupal\Component\Plugin\LazyPluginCollection;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Url;
use Drupal\sms\Direction;
use Drupal\sms\Plugin\SmsGatewayPluginCollection;
use Drupal\sms\Plugin\SmsGatewayPluginInterface;
use Drupal\sms\Plugin\SmsGatewayPluginManagerInterface;

/**
 * Defines storage for an SMS Gateway instance.
 *
 * @ConfigEntityType(
 *   id = "sms_gateway",
 *   label = @Translation("SMS Gateway"),
 *   label_collection = @Translation("SMS Gateways"),
 *   label_singular = @Translation("SMS gateway"),
 *   label_plural = @Translation("SMS gateways"),
 *   label_count = @PluralTranslation(
 *     singular = "@count SMS gateway",
 *     plural = "@count SMS gateways",
 *   ),
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
 *   config_export = {
 *     "id",
 *     "label",
 *     "plugin",
 *     "settings",
 *     "skip_queue",
 *     "incoming_push_path",
 *     "reports_push_path",
 *     "retention_duration_incoming",
 *     "retention_duration_outgoing",
 *   },
 * )
 */
class SmsGateway extends ConfigEntityBase implements SmsGatewayInterface, EntityWithPluginCollectionInterface {

  protected string $id;
  protected ?string $label;

  /**
   * The plugin instance settings.
   *
   * @var array
   *
   * Access settings using:
   * @code
   *   $gateway->getPlugin()->getConfiguration();
   * @endcode
   */
  protected $settings = [];

  /**
   * An SmsGateway plugin ID.
   */
  protected string $plugin;

  /**
   * The plugin collection that holds the plugin for this entity.
   */
  protected ?SmsGatewayPluginCollection $pluginCollection = NULL;

  /**
   * Whether messages sent to this gateway should be sent immediately.
   */
  protected bool $skip_queue = FALSE;

  /**
   * The internal path where incoming messages are received.
   */
  protected ?string $incoming_push_path = NULL;

  /**
   * The internal path where pushed delivery reports can be received.
   */
  protected ?string $reports_push_path = NULL;

  /**
   * How many seconds to hold messages after they are received.
   *
   * @var int<-1, max>
   */
  protected int $retention_duration_incoming = 0;

  /**
   * How many seconds to hold messages after they are sent.
   *
   * @var int<-1, max>
   */
  protected int $retention_duration_outgoing = 0;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values): void {
    parent::preCreate($storage, $values);
    if (!isset($values['incoming_push_path'])) {
      $key = Crypt::randomBytesBase64(16);
      $values['incoming_push_path'] = '/sms/incoming/receive/' . $key;
    }
    if (!isset($values['reports_push_path'])) {
      $key = Crypt::randomBytesBase64(16);
      $values['reports_push_path'] = '/sms/delivery-report/receive/' . $key;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE): void {
    parent::postSave($storage, $update);
    /** @var static|null $original */
    $original = &$this->original;
    $original_path = $original?->getPushReportPath() ?? '';
    if ($original_path != $this->getPushReportPath()) {
      \Drupal::service('router.builder')->setRebuildNeeded();
    }
  }

  /**
   * Encapsulates the creation of the action's LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The action's plugin collection.
   */
  protected function getPluginCollection(): LazyPluginCollection {
    return $this->pluginCollection ??= new SmsGatewayPluginCollection(
      \Drupal::service(SmsGatewayPluginManagerInterface::class),
      // This actually accepts NULL.
      // @phpstan-ignore-next-line
      $this->plugin ?? NULL,
      $this->settings,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['settings' => $this->getPluginCollection()];
  }

  public function getPlugin(): SmsGatewayPluginInterface {
    return $this->getPluginCollection()->get($this->plugin);
  }

  public function getPluginId(): string {
    return $this->plugin;
  }

  public function getSkipQueue(): bool {
    return $this->skip_queue;
  }

  /**
   * {@inheritdoc}
   */
  public function setSkipQueue(bool $skip_queue) {
    $this->skip_queue = $skip_queue;
    return $this;
  }

  public function getPushIncomingPath(): ?string {
    return $this->incoming_push_path;
  }

  /**
   * {@inheritdoc}
   */
  public function setPushIncomingPath(?string $path) {
    $this->incoming_push_path = $path;
    return $this;
  }

  public function getPushReportUrl(): Url {
    return Url::fromRoute('sms.delivery_report.receive.' . $this->id());
  }

  public function getPushReportPath(): ?string {
    return $this->reports_push_path;
  }

  /**
   * {@inheritdoc}
   */
  public function setPushReportPath(?string $path) {
    $this->reports_push_path = $path;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRetentionDuration($direction): int {
    return match ($direction) {
      Direction::INCOMING => $this->retention_duration_incoming,
      Direction::OUTGOING => $this->retention_duration_outgoing,
      default => throw new \InvalidArgumentException(\sprintf('%s is not a valid direction.', $direction)),
    };
  }

  /**
   * {@inheritdoc}
   */
  public function setRetentionDuration($direction, int $retention_duration) {
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

  public function getMaxRecipientsOutgoing(): int {
    $definition = $this->getPlugin()->getPluginDefinition();
    return $definition['outgoingMessageMaxRecipients'] ?? ($definition['outgoing_message_max_recipients'] ?? 1);
  }

  public function supportsIncoming(): bool {
    $definition = $this->getPlugin()
      ->getPluginDefinition();
    return $definition['incoming'] ?? FALSE;
  }

  public function autoCreateIncomingRoute(): bool {
    $definition = $this->getPlugin()->getPluginDefinition();
    return $definition['incomingRoute'] ?? ($definition['incoming_route'] ?? FALSE);
  }

  public function isScheduleAware(): bool {
    $definition = $this->getPlugin()->getPluginDefinition();
    return $definition['scheduleAware'] ?? ($definition['schedule_aware'] ?? FALSE);
  }

  public function supportsReportsPull(): bool {
    $definition = $this->getPlugin()->getPluginDefinition();
    return $definition['reportsPull'] ?? ($definition['reports_pull'] ?? FALSE);
  }

  public function supportsReportsPush(): bool {
    $definition = $this->getPlugin()->getPluginDefinition();
    return $definition['reportsPush'] ?? ($definition['reports_push'] ?? FALSE);
  }

  public function supportsCreditBalanceQuery(): bool {
    $definition = $this->getPlugin()->getPluginDefinition();
    return $definition['creditBalanceAvailable'] ?? ($definition['credit_balance_available'] ?? FALSE);
  }

}
