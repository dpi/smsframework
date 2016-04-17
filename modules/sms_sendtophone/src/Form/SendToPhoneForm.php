<?php

/**
 * @file
 * Contains \Drupal\sms_sendtophone\Form\SendToPhoneForm.
 */

namespace Drupal\sms_sendtophone\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Default controller for the sms_sendtophone module.
 */
class SendToPhoneForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL, $extra = NULL) {
    /** @var \Drupal\user\UserInterface $user */
    $user = User::load($this->currentUser()->id());
    if ($user->hasPermission('send to any number') || (!empty($user->sms_user) && !empty($user->sms_user['number']) && $user->sms_user['status'] == 2)) {
      $form = $this->getForm($form, $form_state, $type, $extra);
    }
    else {
      if ($user->id() > 0 && !empty($user->sms_user) && empty($user->sms_user['number'])) {
        $form['message'] = [
          '#type' => 'markup',
          '#markup' => $this->t('You need to @setup your mobile phone to send messages.',
            array('@setup' => $this->l('setup', Url::fromRoute('entity.user.edit_form', ['user' => $user->id()])))),
        ];
      }
      elseif ($user->id() > 0 && !empty($user->sms_user) && $user->sms_user['status'] != 2) {
        $form['message'] = [
          '#markup' => $this->t('You need to @confirm your mobile phone number to send messages.',
            array('@confirm' => $this->l('confirm', Url::fromRoute('entity.user.edit_form', ['user' => $user->id()])))),
        ];
      }
      else {
        $destination = ['query' => \Drupal::service('redirect.destination')->getAsArray()];
        $form['message'] = [
          '#markup' => $this->t('You do not have permission to send messages. You may need to @signin or @register for an account to send messages to a mobile phone.',
            array(
              '@signin' => $this->l('sign in', Url::fromRoute('user.page', [], $destination)),
              '@register' => $this->l('register', Url::fromRoute('user.register', [], $destination)),
            )),
        ];
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_sendtophone_form';
  }

  /**
   * Builds the form array.
   */
  protected function getForm(array $form, FormStateInterface $form_state, $type = NULL, $extra = NULL) {
    switch ($type) {
      case 'cck':
      case 'field':
      case 'inline':
        $form['message'] = array(
          '#type' => 'value',
          '#value' => $this->getRequest()->get('text'),
        );
        $form['message_preview'] = array(
          '#type' => 'item',
          '#markup' => '<p class="message-preview">' . $this->getRequest()->get('text') . '</p>',
          '#title' => t('Message preview'),
        );
        break;
      case 'node':
        if (is_numeric($extra)) {
          $node = Node::load($extra);
          $form['message_display'] = array(
            '#type' => 'textarea',
            '#title' => t('Message preview'),
            '#description' => t('This URL will be sent to the phone.'),
            '#cols' => 35,
            '#rows' => 2,
            '#attributes' => array('disabled' => TRUE),
            '#default_value' => Url::fromUri('entity:node/' . $node->id(), ['absolute' => TRUE])->toString(),
          );
          $form['message'] = array(
            '#type' => 'value',
            '#value' => Url::fromUri('entity:node/' . $node->id(), ['absolute' => TRUE])->toString(),
          );
        }
        break;
    }

    $user = User::load($this->currentUser()->id());
    $form = array_merge(sms_send_form(), $form);
    if (!empty($user->sms_user)) {
      $form['number']['#default_value'] = $user->sms_user['number'];
      if (is_array($user->sms_user['gateway'])) {
        foreach ($user->sms_user['gateway'] as $option => $value) {
          $form['gateway'][$option]['#default_value'] = $value;
        }
      }
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Send'),
      '#weight' => 20,
    );

    // Add library for CSS styling.
    $form['#attached']['library'] = 'sms_sendtophone/default';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $formatted = sms_formatter($form_state->getValue('number'));
    $form_state->setValue('number', $formatted);
    if (!$formatted) {
      $form_state->setErrorByName('number', $this->t('Please enter a valid phone number.'));
    }
    if ($form_state->isValueEmpty('gateway')) {
      $form_state->setValue('gateway', array());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (sms_send($form_state->getValue('number'), $form_state->getValue('message'), $form_state->getValue('gateway'))) {
      drupal_set_message($this->t('The message "@message" has been sent to @number.',
        array(
          '@message' => $form_state->getValue('message'),
          '@number' => $form_state->getValue('number')
        )));
    }
  }

}
