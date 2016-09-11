<?php

namespace Drupal\sms_sendtophone\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Defines admin overview form.
 */
class AdminOverviewForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_sendtophone_admin_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node_types = NodeType::loadMultiple();
    $types = [];
    foreach ($node_types as $type) {
      $types[$type->get('type')] = $type->get('name');
    }
    $form['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types'),
      '#default_value' => $this->config('sms_sendtophone.settings')->get('content_types'),
      '#options' => $types,
      '#description' => $this->t('Which content types to show the Send To Phone feature.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('sms_sendtophone.settings')
      ->set('content_types', array_filter($form_state->getValue('content_types')))
      ->save();

    drupal_set_message($this->t('The configuration options have been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sms_sendtophone.settings'];
  }

}
