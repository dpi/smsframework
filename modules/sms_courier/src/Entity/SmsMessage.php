<?php

/**
 * @file
 * Contains \Drupal\sms_courier\Entity\SmsMessage.
 */

namespace Drupal\sms_courier\Entity;

use Drupal\courier\ChannelBase;
use Drupal\sms_courier\SmsMessageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Component\Serialization\Json;
use Drupal\courier\Exception\ChannelFailure;

/**
 * Defines storage for a SMS.
 *
 * @ContentEntityType(
 *   id = "sms",
 *   label = @Translation("SMS Message"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\sms_courier\Form\SmsMessage",
 *       "add" = "Drupal\sms_courier\Form\SmsMessage",
 *       "edit" = "Drupal\sms_courier\Form\SmsMessage",
 *       "delete" = "Drupal\sms_courier\Form\SmsMessage",
 *     },
 *   },
 *   base_table = "sms_message",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *   },
 *   links = {
 *     "canonical" = "/sms/{sms}/edit",
 *     "edit-form" = "/sms/{sms}/edit",
 *     "delete-form" = "/sms/{sms}/delete",
 *   }
 * )
 */
class SmsMessage extends ChannelBase implements SmsMessageInterface {

  /**
   * {@inheritdoc}
   *
   * Returns singular recipient.
   */
  public function getRecipients() {
    return [$this->get('phone')->value];
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipient() {
    return $this->get('phone')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecipient($phone_number) {
    $this->set('phone', ['value' => $phone_number]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->get('message')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage($message) {
    $this->set('message', ['value' => $message]);
  }

  /**
   * {@inheritdoc}
   *
   * This method is not applicable to Courier. Only use in current request.
   */
  public function getSender() {
    // @todo. This method isnt actually used anywhere.
    return $this->sender;
  }

  /**
   * {@inheritdoc}
   *
   * This method is not applicable to Courier. Only use in current request.
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * {@inheritdoc}
   *
   * This method is not applicable to Courier. Only use in current request.
   */
  public function getOption($name) {
    if (array_key_exists($name, $this->options)) {
      return $this->options[$name];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applyTokens() {
    $tokens = $this->getTokenValues();
    $this->setMessage(\Drupal::token()->replace($this->getMessage(), $tokens));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  function isEmpty() {
    return empty($this->getMessage());
  }

  /**
   * {@inheritdoc}
   */
  static public function sendMessages(array $messages, $options = []) {
    /** @var static[] $messages */
    foreach ($messages as $message) {
      return \Drupal::service('sms_provider.default')->send($message, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('SMS ID'))
      ->setDescription(t('The SMS ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['phone'] = BaseFieldDefinition::create('telephone')
      ->setLabel(t('Phone'))
      ->setDescription(t('Phone number.'))
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'hidden',
      ]);

    $fields['message'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Message'))
      ->setDescription(t('The SMS message.'))
      ->setDefaultValue('')
      ->setSetting('max_length', 160)
      ->setSetting('is_ascii', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 50,
      ]);

    return $fields;
  }

}
