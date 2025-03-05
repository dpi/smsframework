<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumber\Exception;

final class NoPhoneNumberException extends \Exception {

  public function __construct() {
    parent::__construct(
      message: 'Attempted to send an SMS to entity without a phone number.',
    );
  }

}
