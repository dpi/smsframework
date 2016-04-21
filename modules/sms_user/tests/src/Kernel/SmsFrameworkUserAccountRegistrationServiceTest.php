<?php

/**
 * @file
 * Contains \Drupal\Tests\sms_user\Kernel\SmsFrameworkUserAccountRegistrationServiceTest.
 */

namespace Drupal\Tests\sms_user\Kernel;

use Drupal\Tests\sms\Kernel\SmsFrameworkKernelBase;
use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Entity\SmsMessageInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\sms\Entity\PhoneNumberSettings;

/**
 * Tests account registration.
 *
 * @group SMS Framework
 * @coversDefaultClass \Drupal\sms_user\AccountRegistration
 */
class SmsFrameworkUserAccountRegistrationServiceTest extends SmsFrameworkKernelBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'sms', 'sms_user', 'sms_test_gateway', 'user', 'telephone', 'dynamic_entity_reference', 'field'];

  /**
   * @var \Drupal\sms_user\AccountRegistrationInterface
   *
   * The account registration service.
   */
  protected $accountRegistration;

  /**
   * @var \Drupal\sms\Provider\SmsProviderInterface
   *
   * The default SMS provider.
   */
  protected $smsProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installConfig('sms_user');

    $this->accountRegistration = $this->container->get('sms_user.account_registration');
    $this->smsProvider = $this->container->get('sms_provider');

    $this->gateway = $this->createMemoryGateway(['skip_queue' => TRUE]);
    $this->smsProvider->setDefaultGateway($this->gateway);

    $this->installEntitySchema('user');
    $this->installEntitySchema('sms');
    $this->installEntitySchema('sms_phone_number_verification');

    $this->phoneField = FieldStorageConfig::create([
      'entity_type' => 'user',
      'field_name' => Unicode::strtolower($this->randomMachineName()),
      'type' => 'telephone',
    ]);
    $this->phoneField->save();

    FieldConfig::create([
      'entity_type' => 'user',
      'bundle' => 'user',
      'field_name' => $this->phoneField->getName(),
    ])->save();

    $this->phoneNumberSettings = PhoneNumberSettings::create();
    $this->phoneNumberSettings
      ->setPhoneNumberEntityTypeId('user')
      ->setPhoneNumberBundle('user')
      ->setFieldName('phone_number', $this->phoneField->getName())
      ->setVerificationMessage($this->randomString())
      ->save();
  }

  /**
   * Ensure incoming SMS does not create messages or users.
   */
  public function testIncomingIgnored() {
    $username = $this->randomMachineName();
    $email = 'email@email.com';
    $message = "E " . $email . "\nU " . $username;

    $this->sendIncomingMessage('+123', $message);
    $this->assertEquals(0, count($this->getTestMessages($this->gateway)), 'No messages were created');
    $this->assertEquals(0, count(User::loadMultiple()), 'No users exist.');
  }

  /**
   * Test user is created if a unrecognised phone number is used as sender.
   */
  public function testUnrecognisedCreateUser() {
    $this->config('sms_user.settings')
      ->set('account_registration.all_unknown_numbers.status', 1)
      ->set('account_registration.all_unknown_numbers.reply.status', 1)
      ->save();

    $this->assertEquals(0, count(User::loadMultiple()), 'No users exist.');

    $sender_number = '+123123123';
    $this->sendIncomingMessage($sender_number, $this->randomString());

    $this->assertEquals(1, count(User::loadMultiple()), 'One user created.');
  }

  /**
   * Test a user is not created if the sender phone number is already used.
   */
  public function testUnrecognisedCreateUserPhoneNumberExists() {
    $this->config('sms_user.settings')
      ->set('account_registration.all_unknown_numbers.status', 1)
      ->set('account_registration.all_unknown_numbers.reply.status', 1)
      ->save();

    $sender_number = '+123123123';
    $this->createEntityWithPhoneNumber($this->phoneNumberSettings, [$sender_number]);
    $this->resetTestMessages();

    $this->assertEquals(1, count(User::loadMultiple()));


    $this->sendIncomingMessage($sender_number, $this->randomString());
    $this->assertEquals(1, count(User::loadMultiple()));
    $this->assertEquals(0, count($this->getTestMessages($this->gateway)));
  }

  /**
   * Test user is created from a preformatted message.
   */
  public function testPreformattedUserCreated() {
    $this->config('sms_user.settings')
      ->set('account_registration.formatted.status', 1)
      ->set('account_registration.formatted.reply.status', 1)
      ->save();

    $username = $this->randomMachineName();
    $email = 'email@email.com';
    $message = "E " . $email . "\nU " . $username;
    $this->sendIncomingMessage('+123', $message);

    $user = user_load_by_name($username);
    $this->assertTrue($user instanceof UserInterface, 'User was created');
    $this->assertEquals($username, $user->label());
    $this->assertEquals($email, $user->getEmail());
  }

  /**
   * Test if a duplicated placeholder is confirmed.
   */
  public function testPreformattedPlaceholdersSuccess() {
    $this->config('sms_user.settings')
      ->set('account_registration.formatted.status', 1)
      ->set('account_registration.formatted.reply.status', 1)
      ->set('account_registration.formatted.incoming_messages.0', "[password] [username] [password]")
      ->save();

    $username = $this->randomMachineName();
    $password = $this->randomMachineName();

    $message = "$password $username $password";
    $this->sendIncomingMessage('+123123123', $message);
  }

  /**
   * Test if a duplicated placeholder is not confirmed
   */
  public function testPreformattedPlaceholdersFailure() {
    $this->config('sms_user.settings')
      ->set('account_registration.formatted.status', 1)
      ->set('account_registration.formatted.reply.status', 1)
      ->set('account_registration.formatted.incoming_messages.0', "[password] [username] [password]")
      ->save();

    $username = $this->randomMachineName();
    $password = $this->randomMachineName();
    $password2 = $this->randomMachineName();

    $message = "$password $username $password2";
    $this->sendIncomingMessage('+123123123', $message);

    $this->assertFalse(user_load_by_name($username) instanceof UserInterface, 'User was not created');
  }

  /**
   * Test if a user is created despite no email address.
   *
   * @covers ::userIsValid
   */
  public function testUnrecognisedNoEmail() {
    $this->config('sms_user.settings')
      ->set('account_registration.all_unknown_numbers.status', 1)
      ->save();

    $this->assertEquals(0, $this->countUsers());
    $this->sendIncomingMessage('+123123123', $this->randomString());
    $this->assertFalse(empty($this->getLastUser()->getAccountName()));
    $this->assertTrue(empty($this->getLastUser()->getEmail()));
  }

  /**
   * Test if a user is created despite no email address.
   *
   * @covers ::userIsValid
   */
  public function testPreformattedNoEmail() {
    $this->config('sms_user.settings')
      ->set('account_registration.formatted.status', 1)
      ->set('account_registration.formatted.incoming_messages.0', "[username] [password]")
      ->save();

    $this->assertEquals(0, $this->countUsers());

    $username = $this->randomMachineName();
    $message = "$username " . $this->randomMachineName();
    $this->sendIncomingMessage('+123123123', $message);
    $this->assertEquals($username, $this->getLastUser()->getAccountName());
    $this->assertTrue(empty($this->getLastUser()->getEmail()));
  }

  /**
   * Test error builder.
   *
   * @covers ::buildError
   */
  public function testErrorBuilder() {
    $failure_prefix = 'foo: ';
    $this->config('sms_user.settings')
      ->set('account_registration.formatted.status', 1)
      ->set('account_registration.formatted.incoming_messages.0', "[username] [email]")
      ->set('account_registration.formatted.reply.status', 1)
      ->set('account_registration.formatted.reply.message_failure', $failure_prefix . '[error]')
      ->save();

    $username = $this->randomMachineName();
    $email = 'email@domain.tld';
    User::create(['name' => $username, 'mail' => $email])->save();

    $message = "$username " . $this->randomMachineName();
    $this->sendIncomingMessage('+123123123', $message);

    $expected_error = 'The username ' . $username . ' is already taken. This value is not a valid email address. ';
    $actual = $this->getLastTestMessage($this->gateway)->getMessage();
    $this->assertEquals($failure_prefix . $expected_error, $actual);
  }

  /**
   * Test unique username.
   *
   * @covers ::generateUniqueUsername
   */
  public function testUniqueUsername() {
    $this->config('sms_user.settings')
      ->set('account_registration.all_unknown_numbers.status', 1)
      ->save();

    $this->sendIncomingMessage('+123123123', $this->randomString());
    $user1 = $this->getLastUser();
    $this->sendIncomingMessage('+456456456', $this->randomString());
    $user2 = $this->getLastUser();

    $this->assertNotEquals($user1->getAccountName(), $user2->getAccountName());
  }

  /**
   * Send an incoming SMS message.
   *
   * @param string $sender_number
   *   The sender phone number.
   * @param string $message
   *   The message to send inwards.
   */
  protected function sendIncomingMessage($sender_number, $message) {
    /** @var \Drupal\sms\Entity\SmsMessage $incoming */
    $incoming = SmsMessage::create()
      ->setSenderNumber($sender_number)
      ->setDirection(SmsMessageInterface::DIRECTION_INCOMING)
      ->setMessage($message)
      ->addRecipients($this->randomPhoneNumbers(1));
    $this->smsProvider->queue($incoming);
  }

  /**
   * Count number of registered users.
   *
   * @return integer
   *   Number of users in database.
   */
  protected function countUsers() {
    return count(User::loadMultiple());
  }

  /**
   * Count number of registered users.
   *
   * @return \Drupal\user\UserInterface|NULL
   *   Get last created user, or NULL if no users in database.
   */
  protected function getLastUser() {
    $users = User::loadMultiple();
    return $users ? end($users) : NULL;
  }


}
