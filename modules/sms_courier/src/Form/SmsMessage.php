<?php

/**
 * @file
 * Contains \Drupal\sms_courier\Form\SmsMessage.
 */

namespace Drupal\sms_courier\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\courier\Entity\TemplateCollection;
use Drupal\sms_courier\SmsMessageInterface;

/**
 * Form controller for SMS.
 */
class SmsMessage extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state, SmsMessageInterface $sms = NULL) {
    $form = parent::form($form, $form_state, $sms);

    /** @var \Drupal\sms_courier\SMSMessageInterface $sms */
    $sms = $this->entity;

    if (!$sms->isNew()) {
      $form['#title'] = $this->t('Edit SMS');
    }

    $form['tokens'] = [
      '#type' => 'details',
      '#title' => $this->t('Tokens'),
      '#weight' => 51,
    ];
    $template_collection = TemplateCollection::getTemplateCollectionForTemplate($sms);
    if ($context = $template_collection->getContext()) {
      if ($this->moduleHandler->moduleExists('token')) {
        $form['tokens']['list'] = [
          '#theme' => 'token_tree',
          '#token_types' => $context->getTokens(),
        ];
      }
      else {
        $form['tokens']['list'] = [
          '#markup' => $this->t('Available tokens: @token_types', ['@token_types' => implode(', ', $context->getTokens())]),
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $sms = $this->entity;
    $is_new = $sms->isNew();
    $sms->save();

    $t_args = array('%label' => $sms->label());
    if ($is_new) {
      drupal_set_message(t('SMS has been created.', $t_args));
    }
    else {
      drupal_set_message(t('SMS was updated.', $t_args));
    }
  }

}
