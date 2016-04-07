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

  /**
   * Following are implementors of plain SmsMessage interface.
   *
   * @see \Drupal\sms\Entity\SmsMessageInterface
   */

  /**
   * {@inheritdoc}
   */
  public function getRecipients() {
    $recipients = [];
    foreach ($this->get('recipient_phone_number') as $recipient) {
      $recipients[] = $recipient->value;
    }
    return $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function addRecipient($recipient) {
    // Ensure duplicate recipients cannot be added.
    foreach ($this->recipient_phone_number as $item) {
      if ($item->value == $recipient) {
        return $this;
      }
    }
    $this->recipient_phone_number->appendItem($recipient);
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
    $this->recipient_phone_number->filter(function ($item) use ($recipient) {
      return ($item->value != $recipient);
    });
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeRecipients(array $recipients) {
    $this->recipient_phone_number->filter(function ($item) use ($recipients) {
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
    $sender_name = $this->get('sender_name');
    if (isset($sender_name->value)) {
      return $sender_name->value;
    } else {
      return ($sender_entity = $this->getSenderEntity()) ? $sender_entity->label() : NULL;
    }
  }

  /**
   * {@inheritdoc}
   *
   * @param string $sender|NULL
   *   The name of the sender. Or NULL to defer to the label of the sender
   *   entity.
   *
   * @see ::getSender()
   */
  public function setSender($sender) {
    $this->set('sender_name', $sender);
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

  /**
   * Following are implementors of entity interface.
   *
   * @see \Drupal\sms\Entity\SmsMessageInterface
   */

  /**
   * {@inheritdoc}
   */
  public function getDirection() {
    return $this->get('direction')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getGateway() {
    return $this->get('gateway')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setGateway(SmsGatewayInterface $gateway) {
    $this->set('gateway', $gateway);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSenderNumber() {
    return $this->get('sender_phone_number')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSenderNumber($number) {
    $this->set('sender_phone_number', $number);
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
  public function isQueued() {
    return $this->get('queued')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueued($is_queued) {
    $this->set('queued', $is_queued);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSendTime() {
    return $this->get('send_on')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSendTime($send_time) {
    $this->set('send_on', $send_time);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessedTime() {
    return $this->get('processed')->value;
  }
  /**
   * {@inheritdoc}
   */
  public function setProcessedTime($processed) {
    $this->set('processed', $processed);
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

    $fields['direction'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Transmission direction'))
      ->setDescription(t('Transmission direction, See SmsMessageInterface::DIRECTION_*.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', FALSE)
      ->setSetting('size', 'tiny')
      ->setRequired(TRUE);

    // Sender and receivers.
    $fields['sender_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sender name'))
      ->setDescription(t('The name of the sender.'))
      ->setRequired(FALSE);

    $fields['sender_phone_number'] = BaseFieldDefinition::create('telephone')
      ->setLabel(t('Sender phone number'))
      ->setDescription(t('The phone number of the sender.'))
      ->setDefaultValue('')
      ->setRequired(FALSE);

    $fields['sender_entity'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Sender entity'))
      ->setDescription(t('The entity who sent the SMS message.'))
      ->setRequired(FALSE);

    $fields['recipient_phone_number'] = BaseFieldDefinition::create('telephone')
      ->setLabel(t('Recipient phone number'))
      ->setDescription(t('The phone number of the recipient.'))
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

    // Dates.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creation date'))
      ->setDescription(t('The time the SMS message was created.'))
      ->setRequired(TRUE);

    $fields['send_on'] = BaseFieldDefinition::create('created')
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
