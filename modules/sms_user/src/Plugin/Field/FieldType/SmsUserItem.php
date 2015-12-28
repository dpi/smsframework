<?php

/**
 * @file
 * Contains \Drupal\sms_user\Plugin\Field\FieldType\SmsUserItem.
 */

namespace Drupal\sms_user\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;

/**
 * Plugin implementation of the 'sms_user' field type.
 *
 * @FieldType(
 *   id = "sms_user",
 *   label = @Translation("SMS User"),
 *   description = @Translation("An SMS user's number and associated information."),
 *   default_formatter = "sms_user_default",
 *   list_class = "\Drupal\sms_user\Plugin\Field\FieldType\SmsUserItemList"
 * )
 */
class SmsUserItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['number'] = DataDefinition::create('string')
      ->setLabel(t('Phone Number'))
      ->setDescription('The users mobile phone number.')
      ->setComputed(TRUE)
      ->setRequired(FALSE);

    $properties['status'] = DataDefinition::create('integer')
      ->setLabel(t('SMS Status'))
      ->setDescription('The users status of the Mobile Phone number.')
      ->setComputed(TRUE)
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $properties['code'] = DataDefinition::create('string')
      ->setLabel(t('Verification code'))
      ->setComputed(TRUE)
      ->setReadOnly(TRUE);

    $properties['gateway'] = DataDefinition::create('string')
      ->setLabel(t('SMS Gateway'))
      ->setDescription('The SMS Gateway for this user.')
      ->setComputed(TRUE)
      ->setRequired(FALSE);

    $properties['sleep_enabled'] = DataDefinition::create('boolean')
      ->setLabel(t('Sleep enabled status'))
      ->setRequired(FALSE);

    $properties['sleep_start_time'] = DataDefinition::create('integer')
      ->setLabel(t('Sleep start time'))
      ->setRequired(FALSE);

    $properties['sleep_end_time'] = DataDefinition::create('integer')
      ->setLabel(t('Sleep end time'))
      ->setRequired(FALSE);

    $properties['opted_out'] = DataDefinition::create('boolean')
      ->setLabel(t('Opted out SMS'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'number' => array(
          'type' => 'varchar',
          'length' => 32,
        ),
        'status' => array(
          'type' => 'int',
          'unsigned' => TRUE,
        ),
        'code' => array(
          'type' => 'varchar',
          'length' => 16,
          'default' => ''
        ),
        'gateway' => array(
          'type' => 'text',
          'serialize' => TRUE,
        ),
        'sleep_enabled' => array(
          'type' => 'int',
          'size' => 'tiny',
          'unsigned' => TRUE,
          'default' => 0,
        ),
        'sleep_start_time' => array(
          'type' => 'int',
          'size' => 'tiny',
          'unsigned' => TRUE,
          'default' => 0,
        ),
        'sleep_end_time' => array(
          'type' => 'int',
          'size' => 'tiny',
          'unsigned' => TRUE,
          'default' => 0,
        ),
        'opted_out' => array(
          'type' => 'int',
          'size' => 'tiny',
          'unsigned' => TRUE,
          'default' => 0,
        ),
      ),
      'indexes' => array(
        'number' => array('number'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(

    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = array();

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values = [];
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('number')->getValue();
    return $value === NULL || $value === '';
  }

}
