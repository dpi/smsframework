<?php

/**
 * @file
 * Contains \Drupal\sms\Plugin\QueueWorker\SmsProcessor.
 */

namespace Drupal\sms\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sms\Provider\SmsProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transmits SMS messages.
 *
 * @QueueWorker(
 *   id = "sms.messages",
 *   title = @Translation("SMS message processor"),
 *   cron = {"time" = 60}
 * )
 */
class SmsProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The SMS provider.
   *
   * @var \Drupal\sms\Provider\SmsProviderInterface
   */
  protected $smsProvider;

  /**
   * SMS message entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $smsMessageStorage;

  /**
   * Constructs a new SmsProcessor object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\sms\Provider\SmsProviderInterface $sms_provider
   *   The SMS provider.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, SmsProviderInterface $sms_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->smsMessageStorage = $entity_type_manager->getStorage('sms');
    $this->smsProvider = $sms_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('sms_provider')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param $data
   *   - id: integer: SMS message entity ID.
   */
  public function processItem($data) {
    if (isset($data['id'])) {
      $id = $data['id'];
      /** @var \Drupal\sms\Entity\SmsMessageInterface $sms_message */
      if ($sms_message = $this->smsMessageStorage->load($id)) {
        $this->smsProvider
          ->send($sms_message, []);
        $sms_message
          ->setProcessedTime(REQUEST_TIME)
          ->setQueued(FALSE)
          ->save();
      }
    }
  }

}
