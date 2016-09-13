<?php

namespace Drupal\Tests\sms\Kernel;

use Drupal\sms\Message\SmsMessage;
use Drupal\Component\Utility\Html;

/**
 * Tests SMS Framework tokens.
 *
 * @group SMS Framework
 */
class SmsFrameworkTokenTest extends SmsFrameworkKernelBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system', 'sms', 'entity_test', 'user', 'field', 'telephone',
    'dynamic_entity_reference',
  ];

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->tokenService = $this->container->get('token');
    $this->installConfig(['system']);
    $this->installSchema('system', ['router']);
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests 'sms' tokens.
   */
  public function testSmsTokens() {
    $url_options = ['absolute' => TRUE];
    $this->assertEquals(
      \Drupal::url('sms.phone.verify', [], $url_options),
      $this->tokenService->replace('[sms:verification-url]')
    );
  }

  /**
   * Tests verification code token.
   *
   * Special case token.
   */
  public function testVerificationCode() {
    $data['sms_verification_code'] = $this->randomMachineName();
    $this->assertEquals(
      $data['sms_verification_code'],
      $this->tokenService->replace('[sms-message:verification-code]', $data)
    );
  }

  /**
   * Tests 'sms-message' tokens.
   */
  public function testSmsMessageTokens() {
    $phone_numbers = ['+123123123', '+456456456'];
    $message = $this->randomString();
    $sms_message = new SmsMessage();
    $sms_message
      ->setSenderNumber('+999888777')
      ->setMessage($message)
      ->addRecipients($phone_numbers);
    $data['sms-message'] = $sms_message;

    $this->assertEquals(
      $phone_numbers[0],
      $this->tokenService->replace('[sms-message:phone-number]', $data)
    );

    $this->assertEquals(
      Html::escape($message),
      $this->tokenService->replace('[sms-message:message]', $data)
    );
  }

}
