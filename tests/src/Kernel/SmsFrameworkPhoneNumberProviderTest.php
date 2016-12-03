<?php

namespace Drupal\Tests\sms\Kernel;

use Drupal\sms\Exception\NoPhoneNumberException;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\sms\Entity\PhoneNumberSettings;
use Drupal\Component\Utility\Unicode;
use Drupal\sms\Message\SmsMessage;

/**
 * Tests Phone Number Provider.
 *
 * @group SMS Framework
 * @coversDefaultClass \Drupal\sms\Provider\PhoneNumberProvider
 */
class SmsFrameworkPhoneNumberProviderTest extends SmsFrameworkKernelBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'sms', 'entity_test', 'user', 'field', 'telephone',
    'dynamic_entity_reference', 'sms_test_gateway',
  ];

  /**
   * The phone number provider.
   *
   * @var \Drupal\sms\Provider\PhoneNumberProviderInterface
   */
  protected $phoneNumberProvider;

  /**
   * Phone number verification provider.
   *
   * @var \Drupal\sms\Provider\PhoneNumberVerificationInterface
   */
  protected $phoneNumberVerificationProvider;

  /**
   * A telephone field for testing.
   *
   * @var \Drupal\field\FieldStorageConfigInterface
   */
  protected $phoneField;

  /**
   * Phone number settings for entity_test entity type.
   *
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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('sms_phone_number_verification');

    $this->gateway = $this->createMemoryGateway(['skip_queue' => TRUE]);
    $this->setFallbackGateway($this->gateway);

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

      if (!empty($phone_number_verified)) {
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
      ->setSenderNumber('+999888777')
      ->setMessage($this->randomString());
    $this->setExpectedException(NoPhoneNumberException::class);
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
      ->setSenderNumber('+999888777')
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

}
