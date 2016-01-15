<?php

// $Id$

/**
 * @file
 * Token callbacks for the sms module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\sms\Message\SmsMessageInterface;

/**
 * Implements hook_token_info().
 */
function sms_token_info() {
  $info['types']['sms'] = array(
    'name' => t('SMS'),
    'needs-data' => 'sms',
    'description' => t("SMS"),
  );
  $info['tokens']['sms']['phone_number'] = array(
    'name' => t('Number'),
    'description' => t("SMS Phone Number."),
  );
  $info['tokens']['sms']['message'] = array(
    'name' => t('Message'),
    'description' => t("SMS Message."),
  );
  $info['tokens']['sms']['verification-url'] = [
    'name' => t("Verification URL"),
    'description' => t("The URL of the site verification page."),
  ];
  $info['tokens']['sms']['verification-code'] = [
    'name' => t("Verification Code"),
    'description' => t("A verification code for a phone number."),
  ];
  return $info;
}

/**
 * Implements hook_tokens().
 */
function sms_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'sms') {
    if (!empty($data['sms_message']) && ($data['sms_message'] instanceof SmsMessageInterface)) {
      /** @var \Drupal\sms\Message\SmsMessageInterface $sms_message */
      $sms_message = $data['sms']['sms_message'];

      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'phone_number':
            $replacements[$original] = reset($sms_message->getRecipients());
            break;
          case 'message':
            $replacements[$original] = $sms_message->getMessage();
            break;
        }
      }
    }

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'verification-url':
          $url_options = ['absolute' => TRUE];
          $result = \Drupal::url('sms.phone.verify', [], $url_options, TRUE);
          $replacements[$original] = $result->getGeneratedUrl();
          break;
        case 'verification-code':
          if (!empty($data['sms_verification_code'])) {
            $replacements[$original] = $data['sms_verification_code'];
          }
          break;
      }
    }
  }

  return $replacements;
}
