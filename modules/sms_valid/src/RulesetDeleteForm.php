<?php

/**
 * @file
 * Contains \Drupal\sms_valid\RulesetDeleteForm.
 */

namespace Drupal\sms_valid;

use Drupal\Core\Entity\EntityConfirmFormBase;

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
  public function getCancelRoute() {
    return array(
      'route_name' => 'sms_valid.ruleset_edit',
      'route_parameters' => array(
        'sms_ruleset' => $this->entity->id(),
      ),
    );
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
  public function submit(array $form, array &$form_state) {
    // Delete the ruleset.
    $this->entity->delete();
    drupal_set_message($this->t('The ruleset has been deleted.'));
    watchdog('sms_valid', 'Deleted ruleset @name.', array('@name' => $this->entity->label()));
    $form_state['redirect_route'] = array(
      'route_name' => 'sms_valid.ruleset_list',
      'route_parameters' => array(),
    );
  }

}
