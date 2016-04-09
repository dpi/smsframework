<?php

/**
 * @file
 * Contains \Drupal\sms\Provider\SmsQueueProcessor
 */

namespace Drupal\sms\Provider;

/**
 * The SMS Queue Processor.
 */
class SmsQueueProcessor implements SmsQueueProcessorInterface {

  /**
   * The SMS provider.
   *
   * @var \Drupal\sms\Provider\SmsProviderInterface
   */
  protected $smsProvider;

  /**
   * Creates a new instance of SmsQueueProcessor.
   *
   * @param \Drupal\sms\Provider\SmsProviderInterface $sms_provider
   *   The SMS provider.
   */
  public function __construct(SmsProviderInterface $sms_provider) {
    $this->smsProvider = $sms_provider;
  }

  /**
   * @inheritdoc
   */
  public function processUnqueued() {
    //@todo inject
    $queue = \Drupal::queue('sms.messages');
    //@todo inject
    $sms_storage = \Drupal::entityTypeManager()->getStorage('sms');

    $ids = $sms_storage->getQuery()
      ->condition('queued', 0, '=')
      ->condition('send_on', REQUEST_TIME, '<=')
      ->execute();

    /** @var \Drupal\sms\Entity\SmsMessageInterface $sms_message */
    foreach ($sms_storage->loadMultiple($ids) as $sms_message) {
      $data = ['id' => $sms_message->id()];
      if ($queue->createItem($data)) {
        $sms_message
          ->setQueued(TRUE)
          ->save();
      }
    }
  }

}
