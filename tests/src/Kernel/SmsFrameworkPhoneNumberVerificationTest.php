<?php

namespace Drupal\Tests\sms\Kernel;

use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\sms\Entity\PhoneNumberSettings;
use Drupal\sms\Entity\PhoneNumberSettingsInterface;
use Drupal\sms\Entity\PhoneNumberVerificationInterface;
use Drupal\sms\Exception\PhoneNumberSettingsException;

/**
 * Tests Phone Number Provider.
 *
 * @group SMS Framework
 * @coversDefaultClass \Drupal\sms\Provider\PhoneNumberVerification
 */
class SmsFrameworkPhoneNumberVerificationTest extends SmsFrameworkKernelBase {

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

    $this->phoneNumberVerificationProvider = $this->container->get('sms.phone_number.verification');

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
   * Tests read only phone number config helper.
   *
   * @covers ::getPhoneNumberSettings
   */
  public function testGetPhoneNumberSettings() {
    $return = $this->phoneNumberVerificationProvider->getPhoneNumberSettings($this->randomMachineName(), $this->randomMachineName());
    $this->assertNull($return, 'Phone number settings does not exist.');

    $return = $this->phoneNumberVerificationProvider->getPhoneNumberSettings('entity_test', $this->randomMachineName());
    $this->assertNull($return, 'Phone number settings does not exist.');

    $return = $this->phoneNumberVerificationProvider->getPhoneNumberSettings('entity_test', 'entity_test');
    $this->assertTrue($return instanceof PhoneNumberSettingsInterface);
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

    $this->setExpectedException(PhoneNumberSettingsException::class);
    $this->phoneNumberVerificationProvider->getPhoneNumberSettingsForEntity($test_entity_random_bundle);
  }

  /**
   * Tests read only phone number config helper via entity.
   *
   * @covers ::getPhoneNumberSettingsForEntity
   */
  public function testGetPhoneNumberSettingsForEntity() {
    $entity = $this->createEntityWithPhoneNumber($this->phoneNumberSettings);
    $return = $this->phoneNumberVerificationProvider->getPhoneNumberSettingsForEntity($entity);
    $this->assertTrue($return instanceof PhoneNumberSettingsInterface);
  }

  /**
   * Tests get verification by code.
   *
   * @covers ::getPhoneVerificationByCode
   */
  public function testGetPhoneVerificationByCode() {
    $this->createEntityWithPhoneNumber($this->phoneNumberSettings, ['+123123123']);
    $verification = $this->getLastVerification();
    $return = $this->phoneNumberVerificationProvider->getPhoneVerificationByCode($verification->getCode());
    $this->assertEquals($return->id(), $verification->id());
  }

  /**
   * Tests get verification by non-existent code.
   *
   * @covers ::getPhoneVerificationByCode
   */
  public function testGetPhoneVerificationByFakeCode() {
    $this->createEntityWithPhoneNumber($this->phoneNumberSettings, ['+123123123']);
    $return = $this->phoneNumberVerificationProvider->getPhoneVerificationByCode($this->randomMachineName());
    $this->assertFalse($return);
  }

  /**
   * Tests get verification by phone number.
   *
   * @covers ::getPhoneVerificationByPhoneNumber
   */
  public function testGetPhoneVerificationByPhoneNumber() {
    $phone_number1 = '+123123123';
    $this->createEntityWithPhoneNumber($this->phoneNumberSettings, [$phone_number1]);
    // Decoy:
    $phone_number2 = '+456456456';
    $this->createEntityWithPhoneNumber($this->phoneNumberSettings, [$phone_number2]);

    $return = $this->phoneNumberVerificationProvider->getPhoneVerificationByPhoneNumber($phone_number1, NULL);
    $this->assertEquals(1, count($return));
  }

  /**
   * Tests get verification by phone number with verified option.
   *
   * @covers ::getPhoneVerificationByPhoneNumber
   */
  public function testGetPhoneVerificationByPhoneNumberVerified() {
    $phone_number1 = '+123123123';
    $phone_number2 = '+456456456';
    $entity = $this->createEntityWithPhoneNumber($this->phoneNumberSettings, [$phone_number1, $phone_number2]);
    $this->verifyPhoneNumber($entity, $phone_number2);

    $return = $this->phoneNumberVerificationProvider->getPhoneVerificationByPhoneNumber($phone_number1, TRUE);
    $this->assertEquals(0, count($return));

    $return = $this->phoneNumberVerificationProvider->getPhoneVerificationByPhoneNumber($phone_number1, FALSE);
    $this->assertEquals(1, count($return));

    $return = $this->phoneNumberVerificationProvider->getPhoneVerificationByPhoneNumber($phone_number2, FALSE);
    $this->assertEquals(0, count($return));

    $return = $this->phoneNumberVerificationProvider->getPhoneVerificationByPhoneNumber($phone_number2, TRUE);
    $this->assertEquals(1, count($return));
  }

  /**
   * Tests get verification by phone number with entity type ID option.
   *
   * @covers ::getPhoneVerificationByPhoneNumber
   */
  public function testGetPhoneVerificationByPhoneNumberEntityType() {
    $phone_number = '+123123123';
    $this->createEntityWithPhoneNumber($this->phoneNumberSettings, [$phone_number]);

    $return = $this->phoneNumberVerificationProvider->getPhoneVerificationByPhoneNumber($phone_number, NULL, 'entity_test');
    $this->assertEquals(1, count($return));

    $return = $this->phoneNumberVerificationProvider->getPhoneVerificationByPhoneNumber($phone_number, NULL, $this->randomMachineName());
    $this->assertEquals(0, count($return));
  }

  /**
   * Tests get verification by entity.
   *
   * @covers ::getPhoneVerificationByEntity
   */
  public function testGetPhoneVerificationByEntity() {
    $phone_number = '+123123123';
    $entity = $this->createEntityWithPhoneNumber($this->phoneNumberSettings, [$phone_number]);
    $return = $this->phoneNumberVerificationProvider->getPhoneVerificationByEntity($entity, $phone_number);
    $this->assertNotFalse($return);
  }

  /**
   * Tests get verification by entity with phone number without verification.
   *
   * @covers ::getPhoneVerificationByEntity
   */
  public function testGetPhoneVerificationByEntityInvalidPhone() {
    $entity = $this->createEntityWithPhoneNumber($this->phoneNumberSettings, ['+123123123']);
    $return = $this->phoneNumberVerificationProvider->getPhoneVerificationByEntity($entity, '+456456456');
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

    $return = $this->phoneNumberVerificationProvider->newPhoneVerification($entity, $phone_number);
    $this->assertTrue($return instanceof PhoneNumberVerificationInterface);

    // Catch the phone verification message.
    $this->assertEquals(1, count($this->getTestMessages($this->gateway)));

    $verification = $this->getLastVerification();
    $this->assertEquals($entity->id(), $verification->getEntity()->id());
    $this->assertEquals($phone_number, $verification->getPhoneNumber());
  }

}
