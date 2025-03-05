<?php

declare(strict_types=1);

namespace Drupal\Tests\sms\Kernel;

use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\sms\Hook\SmsTokenHooks;
use Drupal\sms\PhoneNumberVerification\CodeGenerator\VerificationCode;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\Recipient;

/**
 * Tests SMS Framework tokens.
 *
 * @group SMS Framework
 * @coversDefaultClass SmsTokenHooks
 */
final class SmsFrameworkTokenTest extends SmsFrameworkKernelBase {

  protected static $modules = [
    'another_entity_iterator',
    'bca',
    'dynamic_entity_reference',
    'entity_test',
    'notifier',
    'sms',
    'system',
    'telephone',
    'user',
    'field',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests 'sms' tokens.
   */
  public function testSmsTokens(): void {
    $url_options = ['absolute' => TRUE];
    static::assertEquals(
      Url::fromRoute('sms.phone.verify', [], $url_options)->toString(),
      $this::token()->replace('[sms:verification-url]'),
    );
  }

  /**
   * Tests verification code token.
   *
   * Special case token.
   */
  public function testVerificationCode(): void {
    $data = [];
    $data[SmsTokenHooks::TOKEN_SMS_VERIFICATION_CODE] = new VerificationCode($this->randomMachineName() . 'z');
    static::assertEquals(
      $data[SmsTokenHooks::TOKEN_SMS_VERIFICATION_CODE]->getCode(),
      $this::token()->replace('[sms-message:verification-code]', $data),
    );
  }

  /**
   * Tests 'sms-message' tokens.
   */
  public function testSmsMessageTokens(): void {
    $message = $this->randomMachineName();
    $data = [];
    $data[SmsTokenHooks::TOKEN_SMS_MESSAGE] = new Notification($message);
    $data[SmsTokenHooks::TOKEN_SMS_RECIPIENT] = new Recipient(
      phone: '+123123123',
    );

    static::assertEquals(
      '+123123123',
      $this::token()->replace('[sms-recipient:phone-number]', $data),
    );

    static::assertEquals(
      $message,
      $this::token()->replace('[sms-message:message]', $data),
    );
  }

  private static function token(): Token {
    return \Drupal::service(Token::class);
  }

}
