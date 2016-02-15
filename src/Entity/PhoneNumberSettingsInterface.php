<?php

/**
 * @file
 * Contains \Drupal\sms\Entity\PhoneNumberSettingsInterface.
 */

namespace Drupal\sms\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

interface PhoneNumberSettingsInterface extends ConfigEntityInterface {

  /**
   * Get phone number settings entity type.
   *
   * @return string
   *   Entity type ID of phone number settings.
   */
  public function getPhoneNumberEntityTypeId();

  /**
   * Set phone number settings entity type.
   *
   * @param string $entity_type_id
   *   Entity type ID of phone number settings.
   *
   * @return $this
   *   Return phone number settings for chaining.
   */
  public function setPhoneNumberEntityTypeId($entity_type_id);

  /**
   * Get phone number settings bundle.
   *
   * @return string
   *   Bundle of phone number settings.
   */
  public function getPhoneNumberBundle();

  /**
   * Set phone number settings bundle.
   *
   * @param string $bundle
   *   Bundle of phone number settings.
   *
   * @return $this
   *   Return phone number settings for chaining.
   */
  public function setPhoneNumberBundle($bundle);

  /**
   * Get message template to send for phone number verification.
   *
   * @return string
   *   Message template to send for phone number verification.
   */
  public function getVerificationMessage();

  /**
   * Set message template to send for phone number verification.
   *
   * @param string $message
   *   Message template to send for phone number verification.
   *
   * @return $this
   *   Return phone number settings for chaining.
   */
  public function setVerificationMessage($message);

  /**
   * Get number of seconds before phone number verifications expire.
   *
   * @return int
   *   Number of seconds before phone number verifications expire.
   */
  public function getVerificationLifetime();

  /**
   * Set number of seconds before phone number verifications expire.
   *
   * @param int $lifetime
   *   Number of seconds before phone number verifications expire.
   *
   * @return $this
   *   Return phone number settings for chaining.
   */
  public function setVerificationLifetime($lifetime);

  /**
   * Determine if phone number field values are removed when phone number
   * verifications expire.
   *
   * @return bool
   *   Whether to remove phone number field values
   */
  public function isVerificationPhoneNumberPurge();

  /**
   * Set if phone number field values are removed when phone number
   * verifications expire.
   *
   * @param $purge
   *   Whether to remove phone number field values
   *
   * @return $this
   *   Return phone number settings for chaining.
   */
  public function setVerificationPhoneNumberPurge($purge);

  /**
   * Get a field mapping.
   *
   * @param $map
   *   ID to map field name, as found in sms.phone.*.*.fields.$map.
   *
   * @return string|NULL
   *   A field name, or NULL if not set.
   */
  public function getFieldName($map);

  /**
   * Get a field mapping.
   *
   * @param string $map
   *   ID to map field name, as found in sms.phone.*.*.fields.$map.
   * @param string|NULL $field_name
   *   A field name, or NULL to unset.
   *
   * @return $this
   *   Return phone number settings for chaining.
   */
  public function setFieldName($map, $field_name);

}