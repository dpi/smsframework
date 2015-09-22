<?php

/**
 * @file
 * Contains \Drupal\sms_user\Plugin\IdentityChannel\SMS\User.
 */

namespace Drupal\sms_user\Plugin\IdentityChannel\SMS;


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

    if (empty($identity->sms_user['number'])) {
      throw new IdentityException('User does not have a phone number.');
    }

    $message->setRecipient($identity->sms_user['number']);
  }

}
