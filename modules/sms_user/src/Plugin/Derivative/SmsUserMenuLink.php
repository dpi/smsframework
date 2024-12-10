<?php

declare(strict_types=1);

namespace Drupal\sms_user\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\sms\Provider\PhoneNumberVerificationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic menu links for SMS User.
 *
 * @see \Drupal\views\Plugin\Menu\ViewsMenuLink
 */
class SmsUserMenuLink extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Constructs a SmsUserMenuLink instance.
   */
  final public function __construct(
    private PhoneNumberVerificationInterface $phoneNumberVerification,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  final public static function create(ContainerInterface $container, $base_plugin_id): static {
    return new static(
      $container->get(PhoneNumberVerificationInterface::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];

    if ($this->phoneNumberVerification->getPhoneNumberSettings('user', 'user') !== NULL) {
      $links['sms_user_phone_number_settings'] = [
        'title' => \t('User phone number'),
        'description' => \t('Set up phone number fields and settings for users.'),
        'route_name' => 'entity.phone_number_settings.edit_form',
        'route_parameters' => ['phone_number_settings' => 'user.user'],
        'parent' => 'user.admin_index',
        'weight' => 21,
      ];
    }

    return $links;
  }

}
