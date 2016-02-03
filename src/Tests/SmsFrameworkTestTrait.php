<?php

/**
 * @file
 * Contains \Drupal\sms\Tests\SmsFrameworkTestTrait.
 */

namespace Drupal\sms\Tests;

use Drupal\sms\Entity\PhoneNumberSettingsInterface;

/**
 * Shared SMS Framework helpers for kernel and web tests.
 */
trait SmsFrameworkTestTrait {

  protected function createEntityWithPhoneNumber(PhoneNumberSettingsInterface $phone_number_settings, $phone_numbers = []) {
    $field_name = $phone_number_settings->getFieldName('phone_number');
    $entity_type_manager = \Drupal::entityTypeManager();
    $test_entity = $entity_type_manager->getStorage('entity_test')
      ->create([
        'name' => $this->randomMachineName(),
      ]);

    foreach ($phone_numbers as $phone_number) {
      $test_entity->{$field_name}[] = $phone_number;
    }

    $test_entity->save();
    return $test_entity;
  }

}
