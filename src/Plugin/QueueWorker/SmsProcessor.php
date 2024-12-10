<?php

declare(strict_types=1);

namespace Drupal\sms\Plugin\QueueWorker;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\sms\Direction;
use Drupal\sms\Entity\SmsMessageInterface;
use Drupal\sms\Provider\SmsProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transmits SMS messages.
 *
 * @QueueWorker(
 *   id = \Drupal\sms\Plugin\QueueWorker\SmsProcessor::PLUGIN_ID,
 *   title = @Translation("SMS message processor"),
 *   cron = {"time" = 60}
 * )
 */
class SmsProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public const PLUGIN_ID = 'sms.messages';

  /**
   * Constructs a new SmsProcessor object.
   */
  final public function __construct(
    array $configuration,
    $plugin_id,
    mixed $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SmsProviderInterface $smsProvider,
    protected TimeInterface $time,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  final public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('sms.provider'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    /** @var array{id: positive-int} $data */
    $id = $data['id'] ?? throw new \LogicException('Missing SMS message id.');

    /** @var \Drupal\sms\Entity\SmsMessageInterface|null $sms_message */
    $sms_message = $this->smsStorage()->load($id);
    if ($sms_message === NULL) {
      return;
    }

    switch ($sms_message->getDirection()) {
      case Direction::INCOMING:
        $this->smsProvider
          ->incoming($sms_message);
        break;

      case Direction::OUTGOING:
        $this->smsProvider
          ->send($sms_message);
        break;
    }

    $duration = $sms_message->getGateway()?->getRetentionDuration($sms_message->getDirection() ?? throw new \LogicException('SMS message missing direction')) ?? NULL;

    // Clean up SMS message now if retention is set to delete immediately.
    if ($duration === 0) {
      $sms_message->delete();
      return;
    }

    $sms_message
      ->setProcessedTime($this->time->getRequestTime())
      ->setQueued(FALSE)
      ->save();
  }

  /**
   * Create a queue item from a message.
   *
   * @phpstan-return array{id: positive-int}
   */
  public static function createItemFrom(SmsMessageInterface $sms): array {
    if ($sms->isNew()) {
      throw new \LogicException('SMS must be saved.');
    }

    /** @var positive-int $id */
    $id = (int) $sms->id();
    return ['id' => $id];
  }

  private function smsStorage(): EntityStorageInterface {
    return $this->entityTypeManager->getStorage('sms');
  }

}
