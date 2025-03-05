<?php

declare(strict_types=1);

namespace Drupal\Tests\sms\Functional;

use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\sms\PhoneNumberVerification\Enum\Verified;

/**
 * Tests phone numbers verification code form.
 *
 * @group SMS Framework
 * @phpstan-import-type SmsVerificationParameter from \Drupal\sms\SmsCompilerPass
 */
final class SmsFrameworkPhoneNumberVerifyFormTest extends SmsFrameworkBrowserTestBase {

  use \Drupal\Tests\sms\Trait\SmsFrameworkTestTrait;


  protected const FIELD_NAME = 'phone_number';

  protected static $modules = ['entity_test'];

  protected function setUp(): void {
    parent::setUp();

    $this->createPhoneNumberSettings('entity_test', 'entity_test', static::FIELD_NAME);

    $this->setContainerParameter('sms.transports', [
      'fakesms+logger' => 'fakesms+logger://default',
    ]);
    $this->setContainerParameter('notifier.channel_policy', [
      'urgent' => ['sms'],
      'high' => ['sms'],
      'medium' => ['sms'],
      'low' => ['sms'],
    ]);
    $this->setContainerParameter('notifier.field_mapping.sms', [
      [
        'entity_type' => 'entity_test',
        'bundle' => 'entity_test',
        'field_name' => static::FIELD_NAME,
        'verifications' => TRUE,
      ],
    ]);
    $this->rebuildContainer();
    $this->resetAll();
  }

  /**
   * Test phone number verification form.
   */
  public function testVerifyFormAccess(): void {
    // Anonymous.
    $this->drupalGet(Url::fromRoute('sms.phone.verify'));
    $this->assertSession()->statusCodeEquals(403);

    // User with permission.
    $account = $this->drupalCreateUser([
      'sms verify phone number',
    ]);
    $this->drupalLogin($account);
    $this->drupalGet(Url::fromRoute('sms.phone.verify'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Verify phone number');
    $this->assertSession()->pageTextContains('Enter the code you received from a SMS message.');
  }

  /**
   * Test phone number verification form.
   */
  public function testVerifyForm(): void {
    $account = $this->drupalCreateUser([
      'sms verify phone number',
    ]);
    $this->drupalLogin($account);

    $testEntity = EntityTest::create(['name' => $this->randomMachineName()]);
    $testEntity->set(static::FIELD_NAME, ['+123']);
    $testEntity->save();

    $verification = static::getLastVerificationOrException();
    $code = $verification->getVerificationCode();
    static::assertNotNull($code);
    static::assertEquals(Verified::Unverified, $verification->getStatus());

    // Invalid code.
    $edit = [];
    $edit['code'] = $this->randomMachineName();
    $this->drupalGet(Url::fromRoute('sms.phone.verify'));
    $this->submitForm($edit, 'Verify code');
    $this->assertSession()->pageTextContains('Invalid verification code.');

    // Valid code.
    $edit['code'] = $code->getCode();
    $this->drupalGet(Url::fromRoute('sms.phone.verify'));
    $this->submitForm($edit, 'Verify code');
    $this->assertSession()->pageTextContains('Phone number is now verified.');

    // Reset verification code static cache.
    $this->resetAll();
    $verification = static::getLastVerificationOrException();
    static::assertEquals(Verified::Verified, $verification->getStatus());
  }

  /**
   * Test phone number verification form.
   */
  public function testVerifyFormFlood(): void {
    $account = $this->drupalCreateUser([
      'sms verify phone number',
    ]);
    $this->drupalLogin($account);

    // Submit \Drupal\sms\PhoneNumberVerification\Form\Verification::FLOOD_LIMIT
    // times.
    $edit = [];
    for ($i = 0; $i < 5; ++$i) {
      $edit['code'] = $this->randomMachineName();
      $this->drupalGet(Url::fromRoute('sms.phone.verify'));
      $this->submitForm($edit, 'Verify code');
    }

    $this->assertSession()->responseNotContains(\t('There has been too many failed verification attempts. Try again later.'));
    $this->drupalGet(Url::fromRoute('sms.phone.verify'));
    $this->submitForm($edit, 'Verify code');
    $this->assertSession()->pageTextContains('There has been too many failed verification attempts. Try again later.');
  }

  /**
   * Test changing verification path.
   */
  public function testVerifyPathSettings(): void {
    $path = '/' . \strtolower($this->randomMachineName());
    /** @var SmsVerificationParameter $defaults */
    $defaults = \Drupal::getContainer()->getParameter('sms.verification');
    $defaults['route']['path'] = $path;
    $this->setContainerParameter('sms.verification', $defaults);
    $this->rebuildContainer();
    $this->resetAll();

    $this->drupalLogin($this->drupalCreateUser([
      'sms verify phone number',
    ]));

    $this->drupalGet('/verify');
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);
  }

}
