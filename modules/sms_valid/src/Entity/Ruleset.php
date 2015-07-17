<?php

/**
 * Contains \Drupal\sms_valid\Entity\Ruleset
 */

namespace Drupal\sms_valid\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Entity representing an sms number validation ruleset
 * @ConfigEntityType(
 *   id = "sms_ruleset",
 *   label = @Translation("SMS Ruleset"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "access" = "Drupal\Entity\EntityAccessController",
 *     "list" = "Drupal\sms_valid\RulesetListForm",
 *     "form" = {
 *       "add" = "Drupal\sms_valid\RulesetForm",
 *       "edit" = "Drupal\sms_valid\RulesetForm",
 *       "delete" = "Drupal\sms_valid\RulesetDeleteForm",
 *     },
 *   },
 *   config_prefix = "ruleset",
 *   entity_keys = {
 *     "id" = "prefix",
 *     "uuid" = "uuid",
 *     "label" = "name"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/smsframework/validation/ruleset/{sms_ruleset}",
 *   }
 * )
 *
 */
class Ruleset extends ConfigEntityBase {
  /**
   * Number prefix/code; 1-65535.
   * @var string
   */
  public $prefix;

  /**
   * Descriptive name for this prefix/ruleset
   * @var string
   */
  public $name;

  /**
   * Active msg directions. See SMS_DIR_* constants.
   * @var int
   */
  public $dirs_enabled = 0;

  /**
   * ISO 3166-1 alpha-2 country code for this ruleset.
   * @var string
   */
  public $iso2;

  /**
   * The rules contained in this ruleset.
   * @var array
   */
  public $rules = array();

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->prefix;
  }
}