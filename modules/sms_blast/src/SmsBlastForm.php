<?php

/**
 * @file
 * Contains \Drupal\sms_blast\SmsBlastForm
 */

namespace Drupal\sms_blast;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SmsBlastForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_blast_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['message'] = array(
      '#type'  => 'textarea',
      '#title' => $this->t('Message'),
      '#cols'  => 60,
      '#rows'  => 5,
    );

    $form['submit'] = array(
      '#type'  => 'submit',
      '#value' => $this->t('Send'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $result = db_select('sms_user', 'su')
      ->fields('su', array('uid'))
      ->condition('status', 2)
      ->execute();

    $count = 0;
    foreach ($result as $row) {
      sms_user_send($row->uid, $form_state->getValue('message'));
      $count++;
    }
    if ($count) {
      drupal_set_message($this->t('The message was sent to %count users.', array('%count' => $count)));
    }
    else {
      drupal_set_message($this->t('There are 0 users with confirmed phone numbers. The message was not sent.'));
    }
  }
}