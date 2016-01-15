<?php

/**
 * @file
 * Contains \Drupal\sms\Form\PhoneNumberSettingsForm.
 */

namespace Drupal\sms\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Phone Number config.
 */
class PhoneNumberSettingsForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $phone_number_config = $this->getEntity();
    $form = parent::buildForm($form, $form_state);

    $this->entityManager = \Drupal::entityManager();

    if ($phone_number_config->isNew()) {
      $bundle_options = [];
      // Generate a list of fieldable bundles which are not events.
      foreach ($this->entityManager->getDefinitions() as $entity_type) {
        if ($entity_type->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface')) {
          foreach ($this->entityManager->getBundleInfo($entity_type->id()) as $bundle => $bundle_info) {
            //@todo prevent listing bundles which already have phone numbers.
            //if (!1) {
            $bundle_options[(string) $entity_type->getLabel()][$entity_type->id() . '|' . $bundle] = $bundle_info['label'];
            //}
          }
        }
      }

      $form['bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Bundle'),
        '#options' => $bundle_options,
        //'#default_value' => id,
        //'#disabled' => !$event_type->isNew(),
        //'#empty_option' => $bundle_options ? NULL : t('No Bundles Available'),
      ];
    }

    $form['verification_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('verification message'),
    ];

    $form['code_lifetime'] = [
      '#type' => 'number',
      '#title' => $this->t('verification code expiration'),
      '#field_suffix' => $this->t('seconds'),
      '#required' => TRUE,
      '#min' => 60,
      '#default_value' => $phone_number_config->duration_verification_code_expire,
    ];

    $form['phone_number_purge'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Purge phone numbers'),
      '#description' => $this->t('Remove phone number if verification code expires.'),
      '#default_value' => $phone_number_config->verification_phone_number_purge,
    ];

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $mgr */
    $mgr = \Drupal::service('entity_field.manager');

    $field_phone_options = [];
    $field_optout_options = [];
    if (!empty($phone_number_config->entity_type) && !empty($phone_number_config->bundle)) {
      $defs = $mgr->getFieldDefinitions($phone_number_config->entity_type, $phone_number_config->bundle);
      foreach ($defs as $def) {
        if ($def->getType() == 'telephone') {
          $field_phone_options[$def->getName()] = $def->getLabel();
        }
        else if ($def->getType() == 'boolean') {
          $field_optout_options[$def->getName()] = (string) $def->getLabel();
        }
      }
    }

    if (!$phone_number_config->isNew()) {
      $form['phone_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Phone number field'),
        '#description' => $this->t('telephone field'),
        '#options' => $field_phone_options,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => !empty($phone_number_config->fields['phone_number']) ? $phone_number_config->fields['phone_number'] : '',
      ];
    }

    $form['optout_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Opt out of automated messages field'),
      '#description' => $this->t('Checkbox field'),
      '#options' => $field_optout_options,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => !empty($phone_number_config->fields['automated_opt_out']) ? $phone_number_config->fields['automated_opt_out'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $phone_number_config = $this->getEntity();

    if ($phone_number_config->isNew()) {
      list($entity_type, $bundle) = explode('|', $form_state->getValue('bundle'));
      $phone_number_config->entity_type = $entity_type;
      $phone_number_config->bundle = $bundle;
    }
    $phone_number_config->duration_verification_code_expire = $form_state->getValue('code_lifetime');
    $phone_number_config->verification_phone_number_purge = (bool) $form_state->getValue('phone_number_purge');

    $phone_number_config->fields['phone_number'] = $form_state->getValue('phone_field') ?: '';
    $phone_number_config->fields['automated_opt_out'] = $form_state->getValue('optout_field') ?: '';

    $saved = $phone_number_config->save();
  }

  /**
   * {@inheritdoc}
   *
   * Callback for `id` form element in SmsGatewayForm->buildForm.
   */
  public function exists($entity_id, array $element, FormStateInterface $form_state) {
//    $query = $this->entityQueryFactory->get('sms_gateway');
//    return (bool) $query->condition('id', $entity_id)->execute();
  }

}
