<?php

/**
 * @file
 * Contains \Drupal\sms\Form\PhoneNumberSettingsForm.
 */

namespace Drupal\sms\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Url;

/**
 * Form controller for phone number settings.
 */
class PhoneNumberSettingsForm extends EntityForm {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\sms\Entity\PhoneNumberSettingsInterface
   */
  protected $entity;

  /**
   * Constructs a new PhoneNumberSettingsForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = &$this->entity;
    $form = parent::buildForm($form, $form_state);

    if ($config->isNew()) {
      $bundles = [];
      $storage = $this->entityTypeManager
        ->getStorage('phone_number_settings');

      // Generate a list of field-able bundles.
      foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
        if ($entity_type->isSubclassOf(ContentEntityInterface::class)) {
          foreach ($this->entityTypeBundleInfo->getBundleInfo($entity_type->id()) as $bundle => $bundle_info) {
            // Do not show combinations with pre-existing phone number settings.
            if (!$storage->load($entity_type->id() . '.' . $bundle)) {
              $bundles[(string) $entity_type->getLabel()][$entity_type->id() . '|' . $bundle] = $bundle_info['label'];
            }
          }
        }
      }

      $form['bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Bundle'),
        '#options' => $bundles,
        '#required' => TRUE,
      ];

      if (!$bundles) {
        $form['bundle']['#empty_option'] = $this->t('No Bundles Available');
      }
    }

    $form['message'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Messages'),
    ];
    $form['message']['verification_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Verification message'),
      '#description' => $this->t('SMS message sent to verify a phone number. The message should contain the verification code and a link to the verification page.'),
      '#default_value' => $config->isNew() ? "Your verification code is '[sms:verification-code]'.\nGo to [sms:verification-url] to verify your phone number.\n- [site:name]" : $config->getVerificationMessage(),
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
      '#description' => $this->t('How long a verification code is valid, before it expires. Existing verification codes are retroactively updated if this setting changes.'),
      '#field_suffix' => $this->t('seconds'),
      '#required' => TRUE,
      '#min' => 60,
      '#default_value' => $config->isNew() ? 3600 : $config->getVerificationLifetime(),
    ];

    $form['expiration']['phone_number_purge'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Purge phone numbers'),
      '#description' => $this->t('Remove phone number if verification code expires.'),
      '#default_value' => $config->isNew() ?: $config->isVerificationPhoneNumberPurge(),
    ];

    $field_options = ['telephone' => [], 'boolean' => []];
    if (!$config->isNew()) {
      $field_definitions = $this->entityFieldManager
        ->getFieldDefinitions($config->getPhoneNumberEntityTypeId(), $config->getPhoneNumberBundle());
      foreach ($field_definitions as $field_definition) {
        $field_type = $field_definition->getType();
        if (isset($field_options[$field_type])) {
          $field_options[$field_type][$field_definition->getName()] = $field_definition->getLabel();
        }
      }
    }

    if (!$config->isNew()) {
      $form['field_mapping'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Field mapping'),
      ];
      $form['field_mapping']['phone_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Phone number'),
        '#description' => $this->t('Select the field storing phone numbers.'),
        '#options' => $field_options['telephone'],
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $config->getFieldName('phone_number'),
      ];
      $form['field_mapping']['optout_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Automated messages opt out'),
        '#description' => $this->t('Select the field storing preference to opt out of automated messages.'),
        '#options' => $field_options['boolean'],
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $config->getFieldName('automated_opt_out'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $config = &$this->entity;

    if ($config->isNew()) {
      list($entity_type, $bundle) = explode('|', $form_state->getValue('bundle'));
      $config
        ->setPhoneNumberEntityTypeId($entity_type)
        ->setPhoneNumberBundle($bundle);
    }

    $saved = $config
      ->setVerificationMessage($form_state->getValue('verification_message'))
      ->setVerificationLifetime($form_state->getValue('code_lifetime'))
      ->setVerificationPhoneNumberPurge((bool) $form_state->getValue('phone_number_purge'))
      ->setFieldName('phone_number', $form_state->getValue('phone_field'))
      ->setFieldName('automated_opt_out', $form_state->getValue('optout_field'))
      ->save();

    $t_args['%id'] = $config->id();
    if ($saved == SAVED_NEW) {
      drupal_set_message($this->t('Phone number settings %id created.', $t_args));
    }
    else {
      drupal_set_message($this->t('Phone number settings %id saved.', $t_args));
    }

    $form_state->setRedirectUrl(Url::fromRoute('sms.phone_number_settings.list'));
  }

}
