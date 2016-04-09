<?php

/**
 * @file
 * Contains \Drupal\sms\Form\SmsGatewayForm.
 */

namespace Drupal\sms\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sms\Plugin\SmsGatewayPluginManagerInterface;
use Drupal\sms\Entity\SmsGateway;

/**
 * Form controller for SMS Gateways.
 */
class SmsGatewayForm extends EntityForm {

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   * The gateway manager.
   *
   * @var \Drupal\sms\Plugin\SmsGatewayPluginManagerInterface
   */
  protected $gatewayManager;

  /**
   * @param \Drupal\sms\Plugin\SmsGatewayPluginManagerInterface $gateway_manager
   *   The gateway manager service.
   */
  public function __construct(QueryFactory $query_factory, SmsGatewayPluginManagerInterface $gateway_manager) {
    $this->entityQueryFactory = $query_factory;
    $this->gatewayManager = $gateway_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('plugin.manager.sms_gateway')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway */
    $sms_gateway = $this->getEntity();

    if (!$sms_gateway->isNew()) {
      $form['#title'] = $this->t('Edit gateway %label', [
        '%label' => $sms_gateway->label(),
      ]);
    }

    $form['gateway'] = [
      '#type' => 'details',
      '#title' => $this->t('Gateway'),
      '#open' => TRUE,
    ];

    $form['gateway']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $sms_gateway->label(),
      '#required' => TRUE,
    ];

    $form['gateway']['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $sms_gateway->id(),
      '#machine_name' => [
        'source' => ['gateway', 'label'],
        'exists' => [$this, 'exists'],
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores.',
      ],
      '#disabled' => !$sms_gateway->isNew(),
    ];

    $form['gateway']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#description' => $this->t('Enable this gateway?'),
      '#default_value' => $sms_gateway->status(),
    ];

    $plugins = [];
    foreach ($this->gatewayManager->getDefinitions() as $plugin_id => $definition) {
      $plugins[$plugin_id] = $definition['label'];
    }

    $form['gateway']['plugin_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Gateway'),
      '#options' => $plugins,
      '#required' => TRUE,
      '#disabled' => !$sms_gateway->isNew(),
      '#default_value' => !$sms_gateway->isNew() ? $sms_gateway->getPlugin()->getPluginId() : '',
    ];

    $form['message_queue'] = [
      '#type' => 'details',
      '#title' => $this->t('Message queue'),
      '#open' => TRUE,
    ];

    $form['message_queue']['skip_queue'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip queue'),
      '#description' => $this->t('Whether messages sent to this gateway should skip the load balancing queue. This setting should only be used when debugging and should not be used in production.'),
      '#default_value' => $sms_gateway->getSkipQueue(),
    ];

    $form['message_queue']['retention_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Retention'),
      '#description' => $this->t('How many seconds to keep messages after they are sent. Use zero to never expire.'),
      '#field_suffix' => $this->t('seconds'),
      '#default_value' => $sms_gateway->getRetentionDuration(),
      '#min' => 0,
    ];

    if (!$sms_gateway->isNew()) {
      $form['delivery_report_path'] = [
        '#type' => 'item',
        '#title' => $this->t('Delivery report URL'),
        '#markup' => Url::fromRoute('sms.process_delivery_report', ['sms_gateway' => $sms_gateway->id()], ['absolute' => TRUE])->toString(),
      ];
      $instance = $sms_gateway->getPlugin();
      $form += $instance->buildConfigurationForm($form, $form_state);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway */
    $sms_gateway = $this->getEntity();

    if ($sms_gateway->isNew()) {
      $sms_gateway = SmsGateway::create([
        'plugin' => $form_state->getValue('plugin_id'),
      ]);
      $this->setEntity($sms_gateway);
    }
    else {
      $sms_gateway->getPlugin()
        ->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway */
    $sms_gateway = $this->getEntity();

    if (!$sms_gateway->isNew()) {
      $sms_gateway->getPlugin()
        ->submitConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway */
    $sms_gateway = $this->getEntity();

    $sms_gateway->setStatus($form_state->getValue('status'));
    $saved = $sms_gateway->save();

    if ($saved == SAVED_NEW) {
      drupal_set_message($this->t('Gateway created.'));
    }
    else {
      drupal_set_message($this->t('Gateway saved.'));
    }

    if ($saved == SAVED_NEW) {
      // Redirect to edit form.
      $form_state->setRedirectUrl(Url::fromRoute('entity.sms_gateway.edit_form', [
        'sms_gateway' => $sms_gateway->id(),
      ]));
    }
    else {
      // Back to list page.
      $form_state->setRedirect('sms.gateway.list');
    }
  }

  /**
   * {@inheritdoc}
   *
   * Callback for `id` form element in SmsGatewayForm->buildForm.
   */
  public function exists($entity_id, array $element, FormStateInterface $form_state) {
    $query = $this->entityQueryFactory->get('sms_gateway');
    return (bool) $query->condition('id', $entity_id)->execute();
  }

}
