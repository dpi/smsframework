<?php

/**
 * @file
 * Contains \Drupal\sms\Form\PhoneNumberDeleteForm.
 */

namespace Drupal\sms\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller to delete phone number.
 */
class PhoneNumberDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete SMS phone number %label?', array(
      '%label' => $this->entity->label(),
    ));
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    drupal_set_message($this->t('Phone number %label was deleted.', array(
      '%label' => $this->entity->label(),
    )));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
