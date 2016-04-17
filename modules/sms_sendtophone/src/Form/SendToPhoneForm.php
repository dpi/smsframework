<?php

/**
 * @file
 * Contains \Drupal\sms_sendtophone\Form\SendToPhoneForm.
 */

namespace Drupal\sms_sendtophone\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\sms\Entity\SmsMessageInterface;
use Drupal\user\Entity\User;
use Drupal\sms\Exception\PhoneNumberSettingsException;

/**
 * Default controller for the sms_sendtophone module.
 */
class SendToPhoneForm extends FormBase {

  /**
   * Phone numbers for the authenticated user.
   *
   * @var array
   */
  protected $phone_numbers = [];

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL, $extra = NULL) {
    /** @var \Drupal\sms\Provider\PhoneNumberProviderInterface $phone_number_provider */
    $phone_number_provider = \Drupal::service('sms.phone_number');
    /** @var \Drupal\user\UserInterface $user */
    $user = User::load($this->currentUser()->id());

    // @todo This block should be a route access checker.
    try {
      $this->phone_numbers = $phone_number_provider->getPhoneNumbers($user);
    }
    catch (PhoneNumberSettingsException $e) {}

    if ($user->hasPermission('send to any number') || count($this->phone_numbers)) {
      $form = $this->getForm($form, $form_state, $type, $extra);
    }
    else {
      if (!count($this->phone_numbers)) {
        // User has no phone number, or unconfirmed.
        $form['message'] = [
          '#type' => 'markup',
          '#markup' => $this->t('You need to @setup and confirm your mobile phone to send messages.', [
            '@setup' => $user->toLink('set up', 'edit-form')->toString(),
          ])
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
            '#default_value' => $node->toUrl()->setAbsolute()->toString(),
          );
          $form['message'] = array(
            '#type' => 'value',
            '#value' => $node->toUrl()->setAbsolute()->toString(),
          );
        }
        break;
    }

    $form = array_merge(sms_send_form(), $form);
    if (count($this->phone_numbers)) {
      $form['number']['#default_value'] = reset($this->phone_numbers);
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = User::load($this->currentUser()->id());
    $number = $form_state->getValue('number');
    $message = $form_state->getValue('message');

    $sms_message = \Drupal\sms\Entity\SmsMessage::create()
      ->setDirection(SmsMessageInterface::DIRECTION_OUTGOING)
      ->setMessage($message)
      ->setSenderEntity($user)
      ->addRecipient($number);

    /** @var \Drupal\sms\Provider\SmsProviderInterface $provider */
    $provider = \Drupal::service('sms_provider');
    $provider->queue($sms_message);

    drupal_set_message($this->t('Message has been sent.'));
  }

}
