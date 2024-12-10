<?php

declare(strict_types=1);

namespace Drupal\sms\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller to delete phone number settings.
 */
class PhoneNumberSettingsDeleteForm extends EntityConfirmFormBase {

  /**
   * Constructs a new PhoneNumberSettingsDeleteForm.
   */
  final public function __construct(MessengerInterface $messenger) {
    $this->setMessenger($messenger);
  }

  final public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('messenger'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete SMS phone number settings %label?', [
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('sms.phone_number_settings.list');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->entity->delete();

    $this->messenger()->addMessage($this->t('Phone number settings %label was deleted.', [
      '%label' => $this->entity->label(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
