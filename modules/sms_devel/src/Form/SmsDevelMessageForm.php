<?php

namespace Drupal\sms_devel\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\sms\Entity\SmsGateway;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sms\Provider\SmsProviderInterface;
use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Entity\SmsMessageInterface;

/**
 * Simulate a message being sent or received.
 */
class SmsDevelMessageForm extends FormBase {

  /**
   * The SMS Provider.
   *
   * @var \Drupal\sms\Provider\SmsProviderInterface
   */
  protected $smsProvider;

  /**
   * The message.
   *
   * @var \Drupal\sms\Entity\SmsMessageInterface
   */
  protected $message;

  /**
   * Creates an new SendForm object.
   *
   * @param \Drupal\sms\Provider\SmsProviderInterface $sms_provider
   *   The SMS service provider.
   */
  public function __construct(SmsProviderInterface $sms_provider) {
    $this->smsProvider = $sms_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sms_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_devel_message_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['number'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone number'),
      '#required' => TRUE,
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
    ];

    foreach (SmsGateway::loadMultiple() as $sms_gateway) {
      $gateways[$sms_gateway->id()] = $sms_gateway->label();
    }

    $form['gateway'] = [
      '#type' => 'select',
      '#title' => $this->t('Gateway'),
      '#description' => $this->t('Select a gateway to route the message. The <em>automatic</em> option uses internal rules to decide the destination gateway.'),
      '#options' => $gateways,
      '#empty_option' => '- Automatic -',
    ];

    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Options'),
      '#open' => TRUE,
    ];
    $form['options']['skip_queue'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force skip queue'),
      '#description' => $this->t('Send or receive the message immediately. If the gateway-specific skip queue setting is turned on, then this option is already applied.'),
      '#default_value' => TRUE,
    ];
    $form['options']['automated'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automated'),
      '#description' => $this->t('Flag this message as automated.'),
      '#default_value' => TRUE,
    ];
    $form['options']['send_on'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Send on'),
      '#description' => $this->t('Send this message on this date (server time). This option only applies to messages in the queue.'),
      '#default_value' => new DrupalDateTime('now'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['receive'] = [
      '#type' => 'submit',
      '#value' => $this->t('Receive'),
      '#submit' => ['::submitReceive'],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
      '#submit' => ['::submitSend'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $number = $form_state->getValue('number');
    $message = $form_state->getValue('message');
    $automated = !empty($form_state->getValue('automated'));
    $this->message = SmsMessage::create()
      ->addRecipient($number)
      ->setMessage($message)
      ->setAutomated($automated);

    $send_on = $form_state->getValue('send_on');
    if ($send_on instanceof DrupalDateTime) {
      $this->message->setSendTime($send_on->format('U'));
    }

    $gateway = $form_state->getValue('gateway');
    if (!empty($gateway)) {
      $this->message->setGateway(SmsGateway::load($gateway));
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  function submitReceive(array &$form, FormStateInterface $form_state) {
    $this->message->setDirection(SmsMessageInterface::DIRECTION_INCOMING);

    if ($form_state->getValue('skip_queue')) {
      $this->smsProvider->incoming($this->message);
      drupal_set_message($this->t('Message received.'));
    }
    else {
      $this->smsProvider->queue($this->message);
      drupal_set_message($this->t('Message added to the incoming queue.'));
    }

  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  function submitSend(array &$form, FormStateInterface $form_state) {
    $this->message->setDirection(SmsMessageInterface::DIRECTION_OUTGOING);

    if ($form_state->getValue('skip_queue')) {
      $this->smsProvider->send($this->message);
      drupal_set_message($this->t('Message sent.'));
    }
    else {
      $this->smsProvider->queue($this->message);
      drupal_set_message($this->t('Message added to the outgoing queue.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  function submitForm(array &$form, FormStateInterface $form_state) {}

}
