<?php

/**
 * @file
 * Contains \Drupal\sms\Provider\SmsQueueProcessor
 */

namespace Drupal\sms\Provider;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * The SMS Queue Processor.
 */
class SmsQueueProcessor implements SmsQueueProcessorInterface {

  /**
   * SMS gateway config storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $smsGatewayStorage;

  /**
   * SMS message entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $smsMessageStorage;

  /**
   * The SMS provider.
   *
   * @var \Drupal\sms\Provider\SmsProviderInterface
   */
  protected $smsProvider;

  /**
   * Creates a new instance of SmsQueueProcessor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\sms\Provider\SmsProviderInterface $sms_provider
   *   The SMS provider.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, SmsProviderInterface $sms_provider) {
    $this->smsGatewayStorage = $entity_type_manager->getStorage('sms_gateway');
    $this->smsMessageStorage = $entity_type_manager->getStorage('sms');
    $this->smsProvider = $sms_provider;
  }

  /**
   * @inheritdoc
   */
  public function processUnqueued() {
    //@todo inject
    $queue = \Drupal::queue('sms.messages');

    $ids = $this->smsMessageStorage
      ->getQuery()
      ->condition('queued', 0, '=')
      ->condition('processed', NULL, 'IS NULL')
      ->condition('send_on', REQUEST_TIME, '<=')
      ->execute();

    /** @var \Drupal\sms\Entity\SmsMessageInterface $sms_message */
    foreach ($this->smsMessageStorage->loadMultiple($ids) as $sms_message) {
      $data = ['id' => $sms_message->id()];
      if ($queue->createItem($data)) {
        $sms_message
          ->setQueued(TRUE)
          ->save();
      }
    }
  }

  /**
   * @inheritdoc
   */
  public function garbageCollection() {
    /** @var \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway */
    foreach ($this->smsGatewayStorage->loadMultiple() as $sms_gateway) {
      $lifetime = $sms_gateway->getRetentionDuration();
      if ($lifetime !== 0) {
        $ids = $this->smsMessageStorage
          ->getQuery()
          ->condition('gateway', $sms_gateway->id(), '=')
          ->condition('queued', 0)
          ->condition('processed', NULL, 'IS NOT NULL')
          ->condition('processed', REQUEST_TIME - $lifetime, '<=')
          ->execute();
        $this->smsMessageStorage->delete($this->smsMessageStorage->loadMultiple($ids));
      }
    }
  }

}
