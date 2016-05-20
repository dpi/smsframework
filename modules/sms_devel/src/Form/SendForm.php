<?php

/**
 * @file
 * Contains \Drupal\sms_devel\Form\SendForm
 */

namespace Drupal\sms_devel\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sms\Message\SmsMessage;

class SendForm extends FormBase {

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
    // Message to the user about the form.
    $form['about'] = array(
      '#type' => 'item',
      '#value' => 'This is a basic form that contains:<ul><li>include sms_send_form()</li><li>message text field</li><li>submit button</li></ul>The form validation includes sms_send_form_validate().<br/>The form submission includes sms_send_form_submit() which sends the message, and a little note that the form submitted ok.',
    );

    // Include the sms_send_form from the SMS Framework core.
    $form = array_merge($form, sms_send_form());

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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    sms_send_form_validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  function submitForm(array &$form, FormStateInterface $form_state) {
    $phone_number = $form_state->getValue('number');
    $message = $form_state->getValue('message');
    $t_args = [
      '@number'  => $phone_number,
      '@message' => $message,
    ];

    if ($form_state->getTriggeringElement()['#value'] === $form_state->getValue('submit')) {
      sms_send_form_submit($form, $form_state);
      drupal_set_message($this->t('Form submitted ok for number @number and message: @message', $t_args));
    }
    elseif ($form_state->getTriggeringElement()['#value'] === $form_state->getValue('receive')) {
      $sms_message = (new SmsMessage())
        ->addRecipient($phone_number)
        ->setMessage($message);

      /** @var \Drupal\sms\Provider\SmsProviderInterface $provider */
      $provider = \Drupal::service('sms_provider');
      $provider->incoming($sms_message);

      drupal_set_message($this->t('Message received from number @number and message: @message', $t_args));
    }
  }

}
