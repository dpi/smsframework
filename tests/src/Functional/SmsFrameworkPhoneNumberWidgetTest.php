<?php

declare(strict_types=1);

namespace Drupal\Tests\sms\Functional;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\sms\PhoneNumberVerification\Enum\Verified;
use Drupal\Tests\sms\Trait\SmsFrameworkTestTrait;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests phone numbers.
 *
 * @group SMS Framework
 * @phpstan-import-type SmsVerificationParameter from \Drupal\sms\SmsCompilerPass
 */
final class SmsFrameworkPhoneNumberWidgetTest extends SmsFrameworkBrowserTestBase {

  use CronRunTrait;

  use SmsFrameworkTestTrait;

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

    $account = $this->drupalCreateUser([
      'sms verify phone number',
      // Required to edit entity_test.
      'administer entity_test content',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Test telephone widget using entity form.
   */
  public function testPhoneNumberWidget(): void {
    $form_field_phone_number = \sprintf('%s[0][value]', static::FIELD_NAME);

    $test_entity = $this->testEntity([]);

    // No verification code created.
    $this->drupalGet($test_entity->toUrl('edit-form'));
    $this->assertSession()->pageTextContains('Enter a phone number. A verification code will be sent as an SMS message, you must enter the code into the verification form within 15 min.');

    // Create verification code, wait for confirmation.
    $this->submitForm([
      $form_field_phone_number => '+123123123',
    ], 'Save');

    $this->drupalGet($test_entity->toUrl('edit-form'));
    $this->assertSession()->pageTextContains('A verification code has been sent to this phone number. Go to the verification form and enter the code. The code will expire if it is not verified in');

    $input = $this->xpath('//input[@name="' . $form_field_phone_number . '" and @disabled="disabled"]');
    static::assertCount(1, $input, 'The phone number text field is disabled.');

    // Verify the code.
    $phone_verification = static::getLastVerificationOrException();
    $phone_verification
      ->setStatus(Verified::Verified)
      ->save();

    // Check phone number is verified.
    $this->drupalGet($test_entity->toUrl('edit-form'));
    $this->assertSession()->responseContains(\t('This phone number is verified. <strong>Warning:</strong> Modifying this phone number will remove verification.'));

    $input = $this->xpath('//input[@name="' . $form_field_phone_number . '" and @disabled="disabled"]');
    static::assertCount(0, $input, 'The phone number text field is enabled.');
  }

  /**
   * Test behavior of widget when verification code expires.
   */
  public function testPhoneNumberWidgetWithExpiredVerificationCode(): void {
    $test_entity = $this->testEntity();

    // Force verification code to expire.
    $phone_verification = static::getLastVerificationOrException();
    $phone_verification
      ->setCreatedDate(new \DateTimeImmutable('1 Jan 1970'))
      ->save();

    $this->drupalGet($test_entity->toUrl('edit-form'));
    $this->assertSession()->responseContains(\t('Verification code expired. Try again later.'));

    $this->cronRun();
    $this->drupalGet($test_entity->toUrl('edit-form'));

    // Ensure phone number was purged.
    $this->assertSession()->fieldValueEquals(static::FIELD_NAME . '[0][value]', '');
    $this->assertSession()->pageTextContains('Enter a phone number. A verification code will be sent as an SMS message, you must enter the code into the verification form within 15 min.');
  }

  /**
   * Test behavior of widget with phone number purge setting.
   */
  public function testPhoneNumberPurgedFieldValueOnExpiration(): void {
    /** @var SmsVerificationParameter $defaults */
    $defaults = \Drupal::getContainer()->getParameter('sms.verification');
    $defaults['unverified']['purge_fields'] = TRUE;
    $this->setContainerParameter('sms.verification', $defaults);
    $this->rebuildContainer();
    $this->resetAll();

    $test_entity = $this->testEntity();

    // Force verification code to expire.
    static::getLastVerificationOrException()
      ->setCreatedDate(new \DateTimeImmutable('1 Jan 1970'))
      ->save();
    $this->cronRun();

    // Ensure phone number value was removed from the field.
    $this->drupalGet($test_entity->toUrl('edit-form'));
    $this->assertSession()->fieldValueEquals(static::FIELD_NAME . '[0][value]', '');
  }

  /**
   * Test behavior of widget with phone number purge setting.
   */
  public function testPhoneNumberNotPurgedFieldValueOnExpiration(): void {
    /** @var SmsVerificationParameter $defaults */
    $defaults = \Drupal::getContainer()->getParameter('sms.verification');
    $defaults['unverified']['purge_fields'] = FALSE;
    $this->setContainerParameter('sms.verification', $defaults);
    $this->rebuildContainer();
    $this->resetAll();

    $test_entity = $this->testEntity();

    // Force verification code to expire.
    static::getLastVerificationOrException()
      ->setCreatedDate(new \DateTimeImmutable('1 Jan 1970'))
      ->save();
    $this->cronRun();

    // Ensure phone number value is still on the field.
    $this->drupalGet($test_entity->toUrl('edit-form'));
    $this->assertSession()->pageTextContains('Save this form to send a new verification code as an SMS message, you must enter the code into the verification form within 15 min.');
    $this->assertSession()->fieldValueEquals(static::FIELD_NAME . '[0][value]', '+123123123');
  }

  /**
   * @phpstan-param non-empty-string[] $phoneNumbers
   */
  protected function testEntity(array $phoneNumbers = ['+123123123']): EntityTest {
    $testEntity = EntityTest::create(['name' => $this->randomMachineName()]);
    $testEntity->set(static::FIELD_NAME, $phoneNumbers);
    $testEntity->save();
    return $testEntity;
  }

}
