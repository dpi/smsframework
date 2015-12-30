<?php

/**
 * @file
 * Contains \Drupal\sms\Lists\SmsGatewayListBuilder.
 */

namespace Drupal\sms\Lists;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Builds a list of SMS Gateway plugins.
 */
class SmsGatewayListBuilder extends ConfigEntityListBuilder {

  /*
   * Default gateway plugin ID.
   *
   * @var string
   */
  protected $defaultGatewayId;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage) {
    parent::__construct($entity_type, $storage);

    /** @var \Drupal\sms\Gateway\GatewayManagerInterface $manager */
    $manager = \Drupal::service('plugin.manager.sms_gateway');
    $this->defaultGatewayId = $manager->getDefaultGateway() ? $manager->getDefaultGateway()->id() : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['gateway'] = $this->t('Gateway');
    $header['status'] = $this->t('Status');
    $header['is_default'] = $this->t('Is site default?');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\sms\SmsGatewayInterface $entity
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['gateway'] = $entity->getPlugin()->getPluginDefinition()['label'];
    $row['status'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');
    $row['is_default'] = $entity->id() == $this->defaultGatewayId ? $this->t('Default') : '';

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $render = parent::render();
    $render['table']['#empty'] = t('No SMS Gateways found.');
    return $render;
  }

}
