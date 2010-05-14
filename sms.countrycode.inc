<?php
// $Id$
/**
 * SMS Framework core module: Country code functions
 *
 * @package sms
 */ 

function sms_country_get_codes_for_send_form() {
  $result = db_query("SELECT code, name FROM {sms_calling_codes} WHERE status_out = 1");

  // Collect the codes
  $codes = array();
  while ($row = db_fetch_object($result)) {
    $codes[$row->code] = $row->name;
  }
  return $codes;
}


// @todo Do we need to validate dir here, or just provide status in the response?
function sms_country_get_countries_for_number($number, $dir = OUTBOUND_DIR) {
  $number = preg_replace("/[^0-9]/", '', $number); // Remove all leading non-digit characters
  $number = trim($number); // Remove leading and trailing whitespace
  $number = ltrim($number, '0'); // Remove leading zeros

  // Make an array of potential prefixes from the given number
  for ($i = 0; $i < 4; $i++) {
    $potential_prefixes[] = substr($number, 0, $i + 1);
  }

  // Prepare a SQL condition for the direction of communication
  if ($dir = OUTBOUND_DIR) {
    $dir_sql_condition = 'status_out = 1';
  } else {
    $dir_sql_condition = 'status_in = 1';
  }

  // Get the potential countries from the DB
  $result = db_query("SELECT code, iso FROM {sms_calling_codes} WHERE code IN (" . db_placeholders($potential_prefixes, 'varchar') . ") AND $dir_sql_condition", $potential_prefixes);

  // Collect the codes
  $codes = array();
  while ($row = db_fetch_object($result)) {
    $codes[$row->code] = $row->iso;
  }
  return $codes;
}


function sms_country_get_codes_for_country_iso($iso) {
  $result = db_query("SELECT code FROM {sms_calling_codes} WHERE iso = '%s'", $iso);

  $codes = array();
  while ($row = db_fetch_object($result)) {
    $codes[] = $row->code;
  }
  return $codes;
}


function sms_country_get_country($iso) {
  $result = db_query("SELECT * FROM {sms_calling_codes} WHERE iso = '%s'", $iso);

  $country = array();
  $country['codes'] = array();
  while ($row = db_fetch_object($result)) {
    $country['name']       = $row->name;
    $country['status_in']  = $row->status_in;
    $country['status_out'] = $row->status_out;
    $country['codes'][]    = $row->code;
  }
  return $country;
}


function sms_country_is_enabled($iso, $dir = OUTBOUND_DIR) {
  // Assume that all rows with $iso have the same status values - select only 1
  $result = db_query("SELECT status_in, status_out FROM {sms_calling_codes} WHERE iso = '%s' LIMIT 1", $iso);
  $statuses = db_fetch_object($result);

  if ($dir == OUTBOUND_DIR) {
    return $statuses->status_out;
  } 
  elseif ($dir == INBOUND_DIR) {
    return $statuses->status_in;
  }
  else {
    return FALSE;
  }
}


function sms_country_set_status($iso, $status = TRUE, $dir = ALL_DIRS) {
  if ($dir == OUTBOUND_DIR) {
    return db_query("UPDATE {sms_calling_codes} SET status_out = %d WHERE iso = '%s'", $status, $iso);
  }
  elseif ($dir == INBOUND_DIR) {
    return db_query("UPDATE {sms_calling_codes} SET status_in  = %d WHERE iso = '%s'", $status, $iso);
  }
  elseif ($dir == ALL_DIRS) {
    return db_query("UPDATE {sms_calling_codes} SET status_out = %d, status_in = %d WHERE iso = '%s'", $status, $status, $iso);
  }
}


function sms_country_enable($iso, $dir = ALL_DIRS) {
  sms_country_set_status($iso, TRUE, $dir);
}


function sms_country_disable($iso, $dir = ALL_DIRS) {
  sms_country_set_status($iso, FALSE, $dir);
}
