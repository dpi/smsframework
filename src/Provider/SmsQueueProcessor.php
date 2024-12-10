<?php

declare(strict_types=1);

namespace Drupal\sms\Provider;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\sms\Direction;
use Drupal\sms\Plugin\QueueWorker\SmsProcessor;

/**
 * The SMS Queue Processor.
 */
class SmsQueueProcessor implements SmsQueueProcessorInterface {

  /**
   * Creates a new instance of SmsQueueProcessor.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly QueueFactory $queueFactory,
    private readonly TimeInterface $time,
  ) {
  }

  public function processUnqueued(): void {
    $smsGatewayStorage = $this->entityTypeManager->getStorage('sms_gateway');
    $smsMessageStorage = $this->entityTypeManager->getStorage('sms');

    $ids = [];
    foreach ($smsGatewayStorage->loadMultiple() as $sms_gateway) {
      /** @var \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway */
      $query = $smsMessageStorage
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

    $queue = $this->queueFactory->get(SmsProcessor::PLUGIN_ID, reliable: FALSE);

    /** @var \Drupal\sms\Entity\SmsMessageInterface $sms_message */
    foreach ($smsMessageStorage->loadMultiple($ids) as $sms_message) {
      $data = SmsProcessor::createItemFrom($sms_message);
      if ($queue->createItem($data) !== FALSE) {
        $sms_message
          ->setQueued(TRUE)
          ->save();
      }
    }
  }

  public function garbageCollection(): void {
    $smsGatewayStorage = $this->entityTypeManager->getStorage('sms_gateway');
    $smsMessageStorage = $this->entityTypeManager->getStorage('sms');

    $ids = [];
    /** @var \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway */
    foreach ($smsGatewayStorage->loadMultiple() as $sms_gateway) {
      foreach ([
        Direction::INCOMING,
        Direction::OUTGOING,
      ] as $direction) {
        $lifetime = $sms_gateway->getRetentionDuration($direction);
        if ($lifetime !== -1) {
          $ids += $smsMessageStorage
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
      $smsMessageStorage->delete($smsMessageStorage->loadMultiple($ids));
    }
  }

}
