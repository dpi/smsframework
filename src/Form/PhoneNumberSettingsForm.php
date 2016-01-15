<?php

/**
 * @file
 * Contains \Drupal\sms\Form\PhoneNumberSettingsForm.
 */

namespace Drupal\sms\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

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

    $form['message'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Messages'),
    ];
    $form['message']['verification_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Verification message'),
      '#description' => $this->t('SMS message sent to verify a phone number. The message should contain the verification code and a link to the verification page.'),
      '#default_value' => isset($phone_number_config->verification_message) ? $phone_number_config->verification_message : "Your verification code is '[sms:verification-code]'. \nGo to [sms:verification-url] to verify your phone number. \n - [site:name]",
    ];

    $tokens = ['sms'];
    if ($this->moduleHandler->moduleExists('token')) {
      $form['message']['tokens']['list'] = [
        '#theme' => 'token_tree',
        '#token_types' => $tokens,
      ];
    }
    else {
      foreach ($tokens as &$token) {
        $token = "[$token:*]";
      }
      $form['message']['tokens']['list'] = [
        '#markup' => $this->t('Available tokens include: @token_types', ['@token_types' => implode(' ', $tokens)]),
      ];
    }

    $form['expiration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Verification expiration'),
    ];
    $form['expiration']['code_lifetime'] = [
      '#type' => 'number',
      '#title' => $this->t('Verification code lifetime'),
      '#description' => $this->t('How long a verification code is valid, before it expires.'),
      '#field_suffix' => $this->t('seconds'),
      '#required' => TRUE,
      '#min' => 60,
      '#default_value' => isset($phone_number_config->duration_verification_code_expire) ? $phone_number_config->duration_verification_code_expire : 3600,
    ];

    $form['expiration']['phone_number_purge'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Purge phone numbers'),
      '#description' => $this->t('Remove phone number if verification code expires.'),
      '#default_value' => isset($phone_number_config->verification_phone_number_purge) ? $phone_number_config->verification_phone_number_purge : TRUE,
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
      $form['field_mapping'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Field mapping'),
      ];

      $form['field_mapping']['phone_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Phone number'),
        '#description' => $this->t('Select the field storing phone numbers.'),
        '#options' => $field_phone_options,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => !empty($phone_number_config->fields['phone_number']) ? $phone_number_config->fields['phone_number'] : '',
      ];

      $form['field_mapping']['optout_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Automated messages opt out'),
        '#description' => $this->t('Select the field storing preference to opt out of automated messages.'),
        '#options' => $field_optout_options,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => !empty($phone_number_config->fields['automated_opt_out']) ? $phone_number_config->fields['automated_opt_out'] : '',
      ];
    }

    return $form;
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

    $phone_number_config->verification_message = $form_state->getValue('verification_message');
    $phone_number_config->duration_verification_code_expire = $form_state->getValue('code_lifetime');
    $phone_number_config->verification_phone_number_purge = (bool) $form_state->getValue('phone_number_purge');

    $phone_number_config->fields['phone_number'] = $form_state->getValue('phone_field') ?: '';
    $phone_number_config->fields['automated_opt_out'] = $form_state->getValue('optout_field') ?: '';

    $saved = $phone_number_config->save();
    $t_args['%id'] = $phone_number_config->id();
    if ($saved == SAVED_NEW) {
      drupal_set_message($this->t('Phone number settings %id created.', $t_args));
    }
    else {
      drupal_set_message($this->t('Phone number settings %id saved.', $t_args));
    }

    $form_state->setRedirectUrl(Url::fromRoute('sms.phone_number_settings.list'));
  }

}
