<?php

declare(strict_types=1);

namespace Drupal\sms\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\sms\Exception\SmsStorageException;
use Drupal\sms\Message\SmsDeliveryReportInterface as PlainDeliveryReportInterface;
use Drupal\sms\Message\SmsMessageResultInterface as StdMessageResultInterface;

/**
 * Defines the SMS message result entity.
 *
 * The SMS message result entity is used to store SMS message results.
 *
 * @ContentEntityType(
 *   id = "sms_result",
 *   label = @Translation("SMS Message Result"),
 *   label_collection = @Translation("SMS Message Results"),
 *   label_singular = @Translation("SMS message result"),
 *   label_plural = @Translation("SMS message results"),
 *   label_count = @PluralTranslation(
 *     singular = "@count SMS message result",
 *     plural = "@count SMS message results",
 *   ),
 *   base_table = "sms_result",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class SmsMessageResult extends ContentEntityBase implements SmsMessageResultInterface {

  /**
   * The static cache for delivery reports associated with this SMS result.
   *
   * @var \Drupal\sms\Entity\SmsDeliveryReportInterface[]
   */
  protected array $reports = [];

  public function getError(): ?string {
    return $this->get('error')->value ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setError($error) {
    return $this->set('error', $error);
  }

  public function getErrorMessage(): string {
    return $this->get('error_message')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function setErrorMessage($message) {
    return $this->set('error_message', $message);
  }

  public function getReport($recipient): ?PlainDeliveryReportInterface {
    foreach ($this->getReports() as $report) {
      if ($report->getRecipient() === $recipient) {
        return $report;
      }
    }
    return NULL;
  }

  public function getReports(): array {
    if ($this->reports === []) {
      return $this->reports;
    }

    return $this->getSmsMessage()?->getReports() ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function setReports(array $reports) {
    $this->reports = $reports;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addReport(PlainDeliveryReportInterface $report) {
    $this->reports[] = $report;

    return $this;
  }

  public function getCreditsBalance(): ?float {
    return $this->get('credits_balance')->value ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreditsBalance(?float $balance) {
    $this->set('credits_balance', $balance);

    return $this;
  }

  public function getCreditsUsed(): ?float {
    return $this->get('credits_used')->value ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreditsUsed(?float $credits_used) {
    $this->set('credits_used', $credits_used);

    return $this;
  }

  public function getSmsMessage(): ?SmsMessageInterface {
    /** @var \Drupal\sms\Entity\SmsMessageInterface|null */
    return $this->get('sms_message')->entity ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setSmsMessage(SmsMessageInterface $sms_message) {
    $this->set('sms_message', $sms_message);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['error'] = BaseFieldDefinition::create('string')
      ->setLabel(\t('Error'))
      ->setDescription(\t('The normalized error code.'))
      ->setReadOnly(TRUE)
      ->setRequired(FALSE);

    $fields['error_message'] = BaseFieldDefinition::create('string')
      ->setLabel(\t('Error message'))
      ->setDescription(\t('The description of the error from the gateway.'))
      ->setReadOnly(TRUE)
      ->setDefaultValue('')
      ->setRequired(FALSE);

    $fields['credits_balance'] = BaseFieldDefinition::create('decimal')
      ->setLabel(\t('Credits balance'))
      ->setDescription(\t('The balance of credits after the message was sent.'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['credits_used'] = BaseFieldDefinition::create('decimal')
      ->setLabel(\t('Credits used'))
      ->setDescription(\t('The credits used for the message transaction.'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['sms_message'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'sms')
      ->setLabel(\t('SMS Message'))
      ->setDescription(\t('The parent SMS message.'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    return $fields;
  }

  public function preSave(EntityStorageInterface $storage): void {
    // SMS message result cannot be saved without a parent SMS message.
    if (NULL === $this->getSmsMessage()) {
      throw new SmsStorageException('No parent SMS message specified for SMS message result');
    }
    parent::preSave($storage);
  }

  /**
   * Converts a plain SMS message result into an SMS message result entity.
   *
   * @param \Drupal\sms\Message\SmsMessageResultInterface $sms_result
   *   A plain SMS message result.
   *
   * @return static
   *   An SMS message result entity that can be saved.
   */
  public static function convertFromMessageResult(StdMessageResultInterface $sms_result): static {
    if ($sms_result instanceof SmsMessageResultInterface) {
      return $sms_result;
    }
    $new = static::create();
    $new
      ->setCreditsBalance($sms_result->getCreditsBalance())
      ->setCreditsUsed($sms_result->getCreditsUsed())
      ->setError($sms_result->getError())
      ->setErrorMessage($sms_result->getErrorMessage())
      ->setReports($sms_result->getReports());
    return $new;
  }

}
