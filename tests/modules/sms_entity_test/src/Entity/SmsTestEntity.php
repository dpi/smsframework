<?php

declare(strict_types=1);

namespace Drupal\sms_entity_test\Entity;

use Drupal\bca\Attribute\Bundle;
use Drupal\entity_test\Entity\EntityTest;
use Symfony\Component\Notifier\Recipient\SmsRecipientInterface;

#[Bundle(
  entityType: 'entity_test',
  bundle: 'entity_test',
)]
final class SmsTestEntity extends EntityTest implements SmsRecipientInterface {

  public function getPhone(): string {
    return '+1123123123123';
  }

}
