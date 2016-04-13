<?php

/**
 * @file
 * SMS Framework hooks.
 */

/**
 * Alter gateway plugin definitions.
 *
 * This hook gives you a chance to modify gateways after all plugin definitions
 * are discovered.
 *
 * @param array $gateways
 *   An array of gateway definitions keyed by plugin ID.
 */
function hook_sms_gateway_info_alter(&$gateways) {
  $gateways['log']['label'] = new \Drupal\Core\StringTranslation\TranslatableMarkup('The Logger');
}
