<?php

/**
 * @file
 * Contains \Drupal\Tests\sms\Kernel\SmsFrameworkPhoneNumberProviderTest.
 */

namespace Drupal\Tests\sms\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\sms\Entity\PhoneNumberSettings;
use Drupal\Component\Utility\Unicode;
use Drupal\sms\Message\SmsMessage;
use Drupal\sms\Entity\PhoneNumberVerificationInterface;

/**
 * Tests Phone Number Provider.
 *
 * @group SMS Framework
 * @coversDefaultClass \Drupal\sms\Provider\PhoneNumberProvider
 */
class SmsFrameworkPhoneNumberProviderTest extends SmsFrameworkKernelBase {

  /**
   * @var \Drupal\sms\Provider\PhoneNumberProviderInterface
   */
  protected $phoneNumberProvider;

  /**
   * @var \Drupal\field\FieldStorageConfigInterface
   */
  protected $phoneField;

  /**
   * @var \Drupal\sms\Entity\PhoneNumberSettingsInterface
   */
  protected $phoneNumberSettings;

  /**
   * The default gateway.
   *
   * @var \Drupal\sms\Entity\SmsGatewayInterface
   */
  protected $gateway;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['sms', 'entity_test', 'user', 'field', 'telephone', 'dynamic_entity_reference', 'sms_test_gateway'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('sms_phone_number_verification');

    $sms_provider = $this->container->get('sms_provider.default');
    $this->gateway = $this->createMemoryGateway(['skip_queue' => TRUE]);
    $sms_provider->setDefaultGateway($this->gateway);

    $this->phoneNumberProvider = $this->container->get('sms.phone_number');

    $this->phoneField = FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => Unicode::strtolower($this->randomMachineName()),
      'type' => 'telephone',
    ]);
    $this->phoneField->save();

    FieldConfig::create([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field_name' => $this->phoneField->getName(),
    ])->save();

    $this->phoneNumberSettings = PhoneNumberSettings::create();
    $this->phoneNumberSettings
      ->setPhoneNumberEntityTypeId('entity_test')
      ->setPhoneNumberBundle('entity_test')
      ->setFieldName('phone_number', $this->phoneField->getName())
      ->setVerificationMessage($this->randomString())
      ->save();
  }

  /**
   * Tests phone numbers.
   *
   * @covers ::getPhoneNumbers
   */
  public function testGetPhoneNumbersUnverified() {
    $phone_numbers_all = ['+123123123', '+456456456'];

    // Test zero, one, multiple phone numbers.
    for ($i = 0; $i < 3; $i++) {
      $phone_numbers = array_slice($phone_numbers_all, 0, $i);
      $entity = $this->createEntityWithPhoneNumber($this->phoneNumberSettings, $phone_numbers);

      $return = $this->phoneNumberProvider->getPhoneNumbers($entity, NULL);
      $this->assertEquals($phone_numbers, $return);

      $return = $this->phoneNumberProvider->getPhoneNumbers($entity, FALSE);
      $this->assertEquals($phone_numbers, $return);

      $return = $this->phoneNumberProvider->getPhoneNumbers($entity, TRUE);
      $this->assertEquals([], $return);
    }
  }

  /**
   * Tests phone numbers.
   *
   * @covers ::getPhoneNumbers
   */
  public function testGetPhoneNumbersVerified() {
    $phone_numbers_all = ['+123123123', '+456456456'];

    // Test zero, one, multiple phone numbers.
    for ($i = 0; $i < 3; $i++) {
      $phone_numbers = array_slice($phone_numbers_all, 0, $i);
      $entity = $this->createEntityWithPhoneNumber($this->phoneNumberSettings, $phone_numbers);

      // Verify first phone number.
      // Ensures test verifications don't leak between entities.
      // array_slice()' $preserve_keys ensures original field index is retained.
      $phone_number_verified = array_slice($phone_numbers, 0, 1, TRUE);
      $phone_number_unverified = array_slice($phone_numbers, 1, $i, TRUE);

      if (!empty($phone_number_verified))  {
        $this->verifyPhoneNumber($entity, reset($phone_number_verified));
      }

      $return = $this->phoneNumberProvider->getPhoneNumbers($entity, NULL);
      $this->assertEquals($phone_numbers, $return);

      $return = $this->phoneNumberProvider->getPhoneNumbers($entity, FALSE);
      $this->assertEquals($phone_number_unverified, $return, $entity->id());

      $return = $this->phoneNumberProvider->getPhoneNumbers($entity, TRUE);
      $this->assertEquals($phone_number_verified, $return);
    }
  }

  /**
   * Tests SMS message sent to entities with unverified phone number.
   *
   * @covers ::sendMessage
   */
  public function testSendMessageUnverified() {
    $phone_numbers = ['+123123123'];
    $entity = $this->createEntityWithPhoneNumber($this->phoneNumberSettings, $phone_numbers);
    $this->resetTestMessages();
    $sms_message = new SmsMessage();
    $sms_message
      ->setSender('+999888777')
      ->setMessage($this->randomString());
    $this->setExpectedException(\Drupal\sms\Exception\NoPhoneNumberException::class);
    $this->phoneNumberProvider->sendMessage($entity, $sms_message);
  }

  /**
   * Tests SMS message sent to entities with verified phone number.
   *
   * @covers ::sendMessage
   */
  public function testSendMessageVerified() {
    $phone_numbers = ['+123123123'];
    $entity = $this->createEntityWithPhoneNumber($this->phoneNumberSettings, $phone_numbers);
    $this->resetTestMessages();
    $this->verifyPhoneNumber($entity, $phone_numbers[0]);

    $sms_message = new SmsMessage();
    $sms_message
      ->setSender('+999888777')
      ->setMessage($this->randomString());
    $this->phoneNumberProvider->sendMessage($entity, $sms_message);
    $this->assertEquals(1, count($this->getTestMessages($this->gateway)));
  }

  /**
   * Ensure default behaviour is to send one phone number per entity.
   *
   * @covers ::sendMessage
   */
  public function testSendMessageOneMessage() {
    $phone_numbers = ['+123123123', '+456456456'];
    $entity = $this->createEntityWithPhoneNumber($this->phoneNumberSettings, $phone_numbers);
    $this->resetTestMessages();
    $this->verifyPhoneNumber($entity, $phone_numbers[0]);
    $this->verifyPhoneNumber($entity, $phone_numbers[1]);

    $sms_message = new SmsMessage();
    $sms_message
      ->setMessage($this->randomString());
    $this->phoneNumberProvider->sendMessage($entity, $sms_message);

    $message = $this->getLastTestMessage($this->gateway);
    $this->assertEquals([$phone_numbers[0]], $message->getRecipients(), 'The SMS message is using the first phone number from the entity.');
  }

  /**
   * Tests read only phone number config helper.
   *
   * @covers ::getPhoneNumberSettings
   */
  public function testGetPhoneNumberSettings() {
    $return = $this->phoneNumberProvider->getPhoneNumberSettings($this->randomMachineName(), $this->randomMachineName());
    $this->assertEmpty($return->get());

    $return = $this->phoneNumberProvider->getPhoneNumberSettings('entity_test', $this->randomMachineName());
    $this->assertEmpty($return->get());

    $return = $this->phoneNumberProvider->getPhoneNumberSettings('entity_test', 'entity_test');
    $this->assertNotEmpty($return->get());
  }

  /**
   * Tests read only phone number config helper via entity with no settings..
   *
   * @covers ::getPhoneNumberSettingsForEntity
   */
  public function testGetPhoneNumberSettingsForEntityNoSettings() {
    $test_entity_random_bundle = EntityTest::create([
      'name' => $this->randomMachineName(),
      'type' => $this->randomMachineName(),
    ]);

    $this->setExpectedException(\Drupal\sms\Exception\PhoneNumberSettingsException::class);
    $this->phoneNumberProvider->getPhoneNumberSettingsForEntity($test_entity_random_bundle);
  }

  /**
   * Tests read only phone number config helper via entity.
   *
   * @covers ::getPhoneNumberSettingsForEntity
   */
  public function testGetPhoneNumberSettingsForEntity() {
    $entity = $this->createEntityWithPhoneNumber($this->phoneNumberSettings);
    $return = $this->phoneNumberProvider->getPhoneNumberSettingsForEntity($entity);
    $this->assertNotEmpty($return->get());
  }

  /**
   * Tests get verification by code.
   *
   * @covers ::getPhoneVerificationByCode
   */
  public function testGetPhoneVerificationByCode() {
    $this->createEntityWithPhoneNumber($this->phoneNumberSettings, ['+123123123']);
    $verification = $this->getLastVerification();
    $return = $this->phoneNumberProvider->getPhoneVerificationByCode($verification->getCode());
    $this->assertEquals($return->id(), $verification->id());
  }

  /**
   * Tests get verification by non-existent code.
   *
   * @covers ::getPhoneVerificationByCode
   */
  public function testGetPhoneVerificationByFakeCode() {
    $this->createEntityWithPhoneNumber($this->phoneNumberSettings, ['+123123123']);
    $return = $this->phoneNumberProvider->getPhoneVerificationByCode($this->randomMachineName());
    $this->assertFalse($return);
  }

  /**
   * Tests get verification by entity.
   *
   * @covers ::getPhoneVerificationByEntity
   */
  public function testGetPhoneVerificationByEntity() {
    $phone_number = '+123123123';
    $entity = $this->createEntityWithPhoneNumber($this->phoneNumberSettings, [$phone_number]);
    $return = $this->phoneNumberProvider->getPhoneVerificationByEntity($entity, $phone_number);
    $this->assertNotFalse($return);
  }

  /**
   * Tests get verification by entity with phone number without verification.
   *
   * @covers ::getPhoneVerificationByEntity
   */
  public function testGetPhoneVerificationByEntityInvalidPhone() {
    $entity = $this->createEntityWithPhoneNumber($this->phoneNumberSettings, ['+123123123']);
    $return = $this->phoneNumberProvider->getPhoneVerificationByEntity($entity, '+456456456');
    $this->assertFalse($return);
  }

  /**
   * Tests creating new verification for an entity.
   *
   * @covers ::newPhoneVerification
   */
  public function testNewPhoneVerification() {
    $phone_number = '+123123123';
    $entity = $this->createEntityWithPhoneNumber($this->phoneNumberSettings);

    $return = $this->phoneNumberProvider->newPhoneVerification($entity, $phone_number);
    $this->assertTrue($return instanceof PhoneNumberVerificationInterface);

    // Catch the phone verification message.
    $this->assertEquals(1, count($this->getTestMessages($this->gateway)));

    $verification = $this->getLastVerification();
    $this->assertEquals($entity->id(), $verification->getEntity()->id());
    $this->assertEquals($phone_number, $verification->getPhoneNumber());
  }

}
