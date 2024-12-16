<?php

/**
 * @file
 * Token callbacks for the sms module.
 */

declare(strict_types=1);

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;

/**
 * Implements hook_token_info().
 *
 * @phpstan-return array{types: array<string, mixed>, tokens: array<string, mixed>}
 */
function sms_token_info(): array {
  $info = [];
  $info['types']['sms'] = [
    'name' => t('SMS'),
    'description' => t("SMS Framework tokens."),
  ];
  $info['tokens']['sms']['verification-url'] = [
    'name' => t("Verification URL"),
    'description' => t("The URL of the site verification page."),
  ];
  $info['types']['sms-message'] = [
    'name' => t('SMS Message'),
    'needs-data' => 'sms-message',
    'description' => t("Tokens for an SMS Message."),
  ];
  $info['tokens']['sms-message']['phone-number'] = [
    'name' => t('Phone number'),
    'description' => t("Recipient phone number"),
  ];
  $info['tokens']['sms-message']['message'] = [
    'name' => t('Message'),
    'description' => t("Contents of the SMS message."),
  ];
  $info['tokens']['sms-message']['verification-code'] = [
    'name' => t("Verification Code"),
    'description' => t("A verification code for a phone number."),
  ];
  return $info;
}

/**
 * Implements hook_tokens().
 *
 * @phpstan-param array<string, string> $tokens
 * @phpstan-param array<string, mixed> $data
 * @phpstan-param array<string, mixed> $options
 * @phpstan-return array<string, mixed>
 */
function sms_tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
  $replacements = [];

  if ($type == 'sms') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'verification-url':
          $url_options = ['absolute' => TRUE];
          $result = Url::fromRoute('sms.phone.verify', [], $url_options)->toString(TRUE);
          $replacements[$original] = $result->getGeneratedUrl();
          break;
      }
    }
  }

  if ($type == 'sms-message') {
    /** @var \Drupal\sms\Message\SmsMessageInterface|null $sms_message */
    $sms_message = $data['sms-message'] ?? NULL;
    if ($sms_message !== NULL) {
      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'phone-number':
            $recipients = $sms_message->getRecipients();
            if (count($recipients) > 0) {
              $replacements[$original] = $recipients[array_key_first($recipients)];
            }
            break;

          case 'message':
            $replacements[$original] = $sms_message->getMessage();
            break;
        }
      }
    }

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'verification-code':
          $code = $data['sms_verification_code'];
          if (\is_string($code) && \strlen($code) > 0) {
            $replacements[$original] = $data['sms_verification_code'];
          }
          break;
      }
    }
  }

  return $replacements;
}
