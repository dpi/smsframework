<?php

/**
 * @file
 * Contains \Drupal\sms_courier\Plugin\IdentityChannel\SMS\User.
 */

namespace Drupal\sms_courier\Plugin\IdentityChannel\SMS;


use Drupal\courier\Plugin\IdentityChannel\IdentityChannelPluginInterface;
use Drupal\courier\ChannelInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\courier\Exception\IdentityException;

/**
 * Supports core user entities.
 *
 * @IdentityChannel(
 *   id = "identity:user:sms",
 *   label = @Translation("Drupal user to sms"),
 *   channel = "sms",
 *   identity = "user",
 *   weight = 10
 * )
 */
class User implements IdentityChannelPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function applyIdentity(ChannelInterface &$message, EntityInterface $identity) {
    /** @var \Drupal\user\UserInterface $identity */
    /** @var \Drupal\sms_courier\Entity\SmsMessage $message */

    // @todo: use field defined by administrator.
    if (empty($identity->{'field_phone_number'}->value)) {
      throw new IdentityException('User does not have a phone number.');
    }

    $message->setRecipient($identity->{'field_phone_number'}->value);
  }

}
