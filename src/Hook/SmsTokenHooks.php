<?php

declare(strict_types=1);

namespace Drupal\sms\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;

final class SmsTokenHooks {

  public const TOKEN_SMS = 'sms';
  public const TOKEN_SMS_MESSAGE = 'sms-message';
  public const TOKEN_SMS_RECIPIENT = 'sms-recipient';
  public const TOKEN_SMS_VERIFICATION_CODE = 'sms-verification-code';

  /**
   * Implements hook_token_info().
   *
   * @phpstan-return array{types: array<string, mixed>, tokens: array<string, mixed>}
   */
  #[Hook('token_info')]
  public function tokenInfo(): array {
    $info = [];
    $info['types'][SmsTokenHooks::TOKEN_SMS] = [
      'name' => \t('SMS'),
      'description' => \t('SMS Framework tokens.'),
    ];
    $info['tokens'][SmsTokenHooks::TOKEN_SMS]['verification-url'] = [
      'name' => \t('Verification URL'),
      'description' => \t('The URL of the site verification page.'),
    ];

    $info['types'][SmsTokenHooks::TOKEN_SMS_MESSAGE] = [
      'name' => \t('SMS Message'),
      'needs-data' => SmsTokenHooks::TOKEN_SMS_MESSAGE,
      'description' => \t('Tokens for an SMS Message.'),
    ];
    $info['tokens'][SmsTokenHooks::TOKEN_SMS_MESSAGE]['message'] = [
      'name' => \t('Message'),
      'description' => \t('Contents of the SMS message.'),
    ];
    $info['tokens'][SmsTokenHooks::TOKEN_SMS_MESSAGE]['verification-code'] = [
      'name' => \t('Verification Code'),
      'description' => \t('A verification code for a phone number.'),
    ];

    $info['types'][SmsTokenHooks::TOKEN_SMS_RECIPIENT] = [
      'name' => \t('SMS Recipient'),
      'needs-data' => SmsTokenHooks::TOKEN_SMS_RECIPIENT,
      'description' => \t('Tokens for an SMS Message Recipient.'),
    ];
    $info['tokens'][SmsTokenHooks::TOKEN_SMS_RECIPIENT]['phone-number'] = [
      'name' => \t('Phone number'),
      'description' => \t('A phone number.'),
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
  #[Hook('tokens')]
  public function tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
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

    if ($type == SmsTokenHooks::TOKEN_SMS_RECIPIENT) {
      /** @var \Symfony\Component\Notifier\Recipient\SmsRecipientInterface|null $recipient */
      $recipient = $data[SmsTokenHooks::TOKEN_SMS_RECIPIENT] ?? NULL;
      if ($recipient !== NULL) {
        foreach ($tokens as $name => $original) {
          switch ($name) {
            case 'phone-number':
              $replacements[$original] = $recipient->getPhone();
              break;
          }
        }
      }
    }

    if ($type == SmsTokenHooks::TOKEN_SMS_MESSAGE) {
      /** @var \Symfony\Component\Notifier\Notification\Notification|null $notification */
      $notification = $data[SmsTokenHooks::TOKEN_SMS_MESSAGE] ?? NULL;
      if ($notification !== NULL) {
        foreach ($tokens as $name => $original) {
          switch ($name) {
            case 'message':
              $replacements[$original] = $notification->getSubject();
              break;
          }
        }
      }

      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'verification-code':
            /** @var \Drupal\sms\PhoneNumberVerification\CodeGenerator\VerificationCodeInterface|null $code */
            $code = $data[SmsTokenHooks::TOKEN_SMS_VERIFICATION_CODE] ?? NULL;
            if ($code !== NULL) {
              $replacements[$original] = $code->getCode();
            }
            break;
        }
      }
    }

    return $replacements;
  }

}
