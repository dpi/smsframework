<?php

/**
 * @file
 * Contains \Drupal\sms_valid\RulesetDeleteForm.
 */

namespace Drupal\sms_valid;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the ruleset delete confirmation form.
 */
class RulesetDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the ruleset %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

    /**
     * {@inheritdoc}
     */
  public function getCancelUrl() {
    return new Url('sms_valid.ruleset_edit', ['sms_ruleset' => $this->entity->id()]);
  }

    /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Are you sure you want to delete this ruleset? This action cannot be undone.');
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Delete the ruleset.
    $this->entity->delete();
    drupal_set_message($this->t('The ruleset has been deleted.'));
    $this->logger('sms_valid')->info('Deleted ruleset @name.', array('@name' => $this->entity->label()));
    $form_state->setRedirect('sms_valid.ruleset_list');
  }

}
