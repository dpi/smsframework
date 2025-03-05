<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumber;

use Ramsey\Collection\AbstractCollection;

/**
 * @extends \Ramsey\Collection\AbstractCollection<\Drupal\sms\PhoneNumber\PhoneNumberInterface>
 */
final class PhoneNumbers extends AbstractCollection {

  public function getType(): string {
    return '\\Drupal\\sms\\PhoneNumber\\PhoneNumber';
  }

}
