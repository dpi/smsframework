<?php

/**
 * @file
 * Contains \Drupal\sms\Entity\SmsMessage.
 */

namespace Drupal\sms\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Defines the SMS message entity.
 *
 * The SMS message entity is used to maintain the message while it is queued to
 * send to the gateway. After the message has been sent, the message may persist
 * in the database as an archive record.
 *
 * @ContentEntityType(
 *   id = "sms",
 *   label = @Translation("SMS Message"),
 *   base_table = "sms",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class SmsMessage extends ContentEntityBase implements SmsMessageInterface {

  // From \Drupal\sms\Message\SmsMessageInterface.

  /**
   * {@inheritdoc}
   */
  public function getRecipients() {
    $recipients = [];
    foreach ($this->get('recipient') as $recipient) {
      $recipients[] = $recipient->value;
    }
    return $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function addRecipient($recipient) {
    $this->recipient->appendItem($recipient);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addRecipients(array $recipients) {
    foreach ($recipients as $recipient) {
      $this->addRecipient($recipient);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeRecipient($recipient) {
    $this->recipient->filter(function ($item) use ($recipient) {
      return ($item->value != $recipient);
    });
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeRecipients(array $recipients) {
    $this->recipient->filter(function ($item) use ($recipients) {
      return !in_array($item->value, $recipients);
    });
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return ($first = $this->get('options')->first()) ? $first->getValue() : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($name) {
    $options = $this->getOptions();
    return isset($options[$name]) ? $options[$name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setOption($name, $value) {
    $options = $this->getOptions();
    $options[$name] = $value;
    $this->set('options', $options);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeOption($name) {
    $options = $this->getOptions();
    unset($options[$name]);
    $this->set('options', $options);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSender() {
    return ($sender_entity = $this->getSenderEntity()) ? $sender_entity->label() : '';
  }

  /**
   * {@inheritdoc}
   */
  public function setSender($sender) {
    // Send to the ether.
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
    $this->set('message', $message);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    return $this->get('uuid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getUid() {
    $sender = $this->getSenderEntity();
    return ($sender instanceof UserInterface) ? $sender->id() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setUid($uid) {
    $this->setSenderEntity(User::load($uid));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isAutomated() {
    return $this->get('automated')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAutomated($automated) {
    $this->set('automated', $automated);
    return $this;
  }

  // From \Drupal\sms\Entity\SmsMessageInterface.

  /**
   * {@inheritdoc}
   */
  public function getGateway() {
    $this->get('gateway');
  }

  /**
   * {@inheritdoc}
   */
  public function setGateway($gateway) {
    $this->set('gateway', $gateway);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSenderNumber() {
    return $this->get('sender')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSenderNumber($number) {
    $this->set('sender', $number);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSenderEntity() {
    return $this->get('sender_entity')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setSenderEntity(EntityInterface $entity) {
    $this->set('sender_entity', $entity);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipientEntity() {
    return $this->get('recipient_entity')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecipientEntity(EntityInterface $entity) {
    $this->set('recipient_entity', $entity);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    // Identifiers.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('SMS message ID'))
      ->setDescription(t('The SMS message ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The SMS message UUID.'))
      ->setReadOnly(TRUE);

    $fields['gateway'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Gateway Plugin'))
      ->setDescription(t('The gateway plugin instance.'))
      ->setSetting('target_type', 'sms_gateway')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['reference'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Reference number'))
      ->setDescription(t('An optional tracking number provided by the gateway plugin.'));

    $fields['direction'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Transmission direction'))
      ->setDescription(t('Transmission direction, See SmsMessageInterface::DIRECTION_*.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', FALSE)
      ->setSetting('size', 'tiny');

    // Sender and receivers.
    $fields['sender'] = BaseFieldDefinition::create('telephone')
      ->setLabel(t('Sender phone number'))
      ->setDescription(t('The phone number of the sender.'))
      ->setDefaultValue('')
      ->setRequired(FALSE);

    $fields['sender_entity'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Sender entity'))
      ->setDescription(t('The entity who sent the SMS message.'))
      ->setRequired(FALSE);

    $fields['recipient'] = BaseFieldDefinition::create('telephone')
      ->setLabel(t('Sender phone number'))
      ->setDescription(t('The phone number of the sender.'))
      ->setDefaultValue('')
      ->setRequired(FALSE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED);

    $fields['recipient_entity'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Recipient entity'))
      ->setDescription(t('The entity who received the SMS message.'))
      ->setRequired(FALSE);

    // Meta information.
    $fields['options'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Options'))
      ->setDescription(t('Options to pass to the gateway.'));

    $fields['automated'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Is automated'))
      ->setDescription(t('Whether this SMS message was generated automatically. 0=generated by user action, 1=generated automatically.'))
      ->setDefaultValue(TRUE)
      ->setRequired(TRUE);

    $fields['queued'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Queued'))
      ->setDescription(t('Whether the SMS message is in the queue to be processed.'))
      ->setRequired(FALSE);

    $fields['delivery_status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Delivery status'))
      ->setDescription(t('Delivery status matching codes in SmsDeliveryReportInterface::STATUS_*'))
      ->setRequired(FALSE)
      ->setSetting('size', 'small')
      ->setSetting('unsigned', TRUE);

    // Dates.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creation date'))
      ->setDescription(t('The time the SMS message was created.'))
      ->setRequired(TRUE);

    $fields['send_on'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Send date'))
      ->setDescription(t('The time to send the SMS message.'))
      ->setRequired(TRUE);

    $fields['processed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Processed'))
      ->setDescription(t('The time the SMS message was processed. This value does not indicate whether the message was sent, only that the gateway accepted the request.'))
      ->setRequired(FALSE);

    // Message contents.
    $fields['message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Message'))
      ->setDescription(t('The SMS message.'))
      ->setDefaultValue('')
      ->setRequired(TRUE);

    return $fields;
  }

}
