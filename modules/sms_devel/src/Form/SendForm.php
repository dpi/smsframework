<?php

/**
 * @file
 * Contains \Drupal\sms_devel\Form\SendForm
 */

namespace Drupal\sms_devel\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sms\Provider\SmsProviderInterface;
use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Entity\SmsMessageInterface;

class SendForm extends FormBase {

  /**
   * The SMS Provider.
   *
   * @var \Drupal\sms\Provider\SmsProviderInterface
   */
  protected $smsProvider;

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
    return 'sms_devel_send_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['number'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone number'),
    ];

    // Message text field for the send form.
    $form['message'] = array(
      '#type' => 'textarea',
      '#title' => t('Message'),
      '#rows' => 4,
      '#cols' => 40,
      '#resizable' => FALSE,
    );

    // Submit button for the send form.
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Send Message',
    );

    // Receive Message Button for testing incoming messages.
    $form['receive'] = array(
      '#type' => 'submit',
      '#value' => 'Receive Message',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  function submitForm(array &$form, FormStateInterface $form_state) {
    $sms_message = SmsMessage::create()
      ->addRecipient(sms_formatter($form_state->getValue('number')))
      ->setMessage($form_state->getValue('message'))
      ->setDirection(SmsMessageInterface::DIRECTION_OUTGOING);
    $this->smsProvider->queue($sms_message);

    if ($form_state->getTriggeringElement()['#value'] === $form_state->getValue('submit')) {
      // Display a message to the user.
      drupal_set_message($this->t("Form submitted ok for number @number and message: @message",
        [
          '@number'  => $form_state->getValue('number'),
          '@message' => $form_state->getValue('message')
        ]));
    }
    elseif ($form_state->getTriggeringElement()['#value'] === $form_state->getValue('receive')) {
      // Display a message to the user.
      $number = $form_state->getValue('number');
      $message = $form_state->getValue('message');
      sms_incoming($number, $message);
      drupal_set_message($this->t("Message received from number @number and message: @message",
        [
          '@number'  => $form_state->getValue('number'),
          '@message' => $form_state->getValue('message')
        ]));
    }
  }

}
