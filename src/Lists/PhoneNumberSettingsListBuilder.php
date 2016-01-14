<?php

/**
 * @file
 * Contains \Drupal\sms\Lists\PhoneNumberSettingsListBuilder.
 */

namespace Drupal\sms\Lists;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Builds a list of phone number settings.
 */
class PhoneNumberSettingsListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['entity_type'] = $this->t('Entity type');
    $header['bundle'] = $this->t('Bundle');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\sms\Entity\PhoneNumberSettingsInterface $entity */
    $row['entity_type'] = $entity->getPhoneNumberEntityTypeId();
    $row['bundle'] = $entity->getPhoneNumberBundle();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $render = parent::render();
    $render['table']['#empty'] = t('No phone number settings found.');
    return $render;
  }

}
