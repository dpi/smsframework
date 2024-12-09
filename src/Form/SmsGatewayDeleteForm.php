<?php

declare(strict_types=1);

namespace Drupal\sms\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller to delete SMS Gateway.
 */
class SmsGatewayDeleteForm extends EntityConfirmFormBase {

  /**
   * Constructs a new SmsGatewayDeleteForm.
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
    return $this->t('Are you sure you want to delete SMS gateway %label?', [
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
    return new Url('sms.gateway.list');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->entity->delete();

    $this->messenger()->addMessage($this->t('Gateway %label was deleted.', [
      '%label' => $this->entity->label(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
