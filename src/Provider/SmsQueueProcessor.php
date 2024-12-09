<?php

declare(strict_types=1);

namespace Drupal\sms\Provider;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\sms\Direction;
use Drupal\sms\Plugin\QueueWorker\SmsProcessor;

/**
 * The SMS Queue Processor.
 */
class SmsQueueProcessor implements SmsQueueProcessorInterface {

  protected EntityStorageInterface $smsGatewayStorage;
  protected EntityStorageInterface $smsMessageStorage;

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected QueueInterface $queue;

  /**
   * Creates a new instance of SmsQueueProcessor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    QueueFactory $queueQactory,
    protected SmsProviderInterface $smsProvider,
    protected TimeInterface $time,
  ) {
    $this->smsGatewayStorage = $entityTypeManager->getStorage('sms_gateway');
    $this->smsMessageStorage = $entityTypeManager->getStorage('sms');
    $this->queue = $queueQactory->get(SmsProcessor::PLUGIN_ID, FALSE);
  }

  public function processUnqueued(): void {
    $ids = [];
    foreach ($this->smsGatewayStorage->loadMultiple() as $sms_gateway) {
      /** @var \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway */
      $query = $this->smsMessageStorage
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('gateway', $sms_gateway->id(), '=')
        ->condition('queued', 0, '=')
        ->condition('processed', NULL, 'IS NULL');

      if (!$sms_gateway->isScheduleAware()) {
        $query->condition('send_on', $this->time->getRequestTime(), '<=');
      }

      $ids += $query->execute();
    }

    /** @var \Drupal\sms\Entity\SmsMessageInterface $sms_message */
    foreach ($this->smsMessageStorage->loadMultiple($ids) as $sms_message) {
      $data = SmsProcessor::createItemFrom($sms_message);
      if ($this->queue->createItem($data) !== FALSE) {
        $sms_message
          ->setQueued(TRUE)
          ->save();
      }
    }
  }

  public function garbageCollection(): void {
    $ids = [];
    /** @var \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway */
    foreach ($this->smsGatewayStorage->loadMultiple() as $sms_gateway) {
      foreach ([
        Direction::INCOMING,
        Direction::OUTGOING,
      ] as $direction) {
        $lifetime = $sms_gateway->getRetentionDuration($direction);
        if ($lifetime !== -1) {
          $ids += $this->smsMessageStorage
            ->getQuery()
            ->accessCheck(FALSE)
            ->condition('gateway', $sms_gateway->id(), '=')
            ->condition('queued', 0)
            ->condition('direction', $direction)
            ->condition('processed', NULL, 'IS NOT NULL')
            ->condition('processed', $this->time->getRequestTime() - $lifetime, '<=')
            ->execute();
        }
      }
    }

    if ($ids !== []) {
      $this->smsMessageStorage->delete($this->smsMessageStorage->loadMultiple($ids));
    }
  }

}
