<?php

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
    $data['sms__recipient_phone_number']['table']['join'] = [
      'sms' => [
        'left_field' => 'id',
        'field' => 'entity_id',
      ],
    ];

    $data['sms__recipient_phone_number']['table']['group'] = $this->t('SMS Message');
    $data['sms__recipient_phone_number']['table']['provider'] = $this->entityType->getProvider();

    return $data;
  }

}
