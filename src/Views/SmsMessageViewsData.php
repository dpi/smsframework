<?php

/**
 * @file
 * Contains \Drupal\sms\Views\SmsMessageViewsData.
 */

namespace Drupal\sms\Views;

use Drupal\views\EntityViewsData;

/**
 * Provides views data for SMS message entities.
 */
class SmsMessageViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Direction field.
    $data['sms']['direction']['field']['id'] = 'sms_message_direction';

    // Recipient phone numbers.
    $data['sms']['recipient_phone_numbers'] = [
      'title' => t('Recipient phone numbers'),
      'real field' => 'id',
      'relationship' => [
        'title' => $this->t('Recipient phone numbers'),
        'base' => 'sms__recipient_phone_number',
        'base field' => 'entity_id',
        'id' => 'standard',
        'label' => $this->t('Recipient phone numbers'),
      ],
    ];
    $data['sms__recipient_phone_number']['table']['group'] = $this->t('SMS Message');
    $data['sms__recipient_phone_number']['table']['provider'] = $this->entityType->getProvider();
    $data['sms__recipient_phone_number']['recipient_phone_number_value'] = [
      'title' => t('Recipient phone number'),
      'field' => [
        'id' => 'standard',
      ],
    ];

    return $data;
  }

}
