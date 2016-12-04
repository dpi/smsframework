<?php

namespace Drupal\Tests\sms_user\Kernel;

use Drupal\Tests\sms\Kernel\SmsFrameworkKernelBase;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\sms\Entity\SmsMessage;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\sms\Entity\PhoneNumberSettings;
use Drupal\sms\Entity\SmsGatewayInterface;
use Drupal\sms\Direction;

/**
 * Tests account registration.
 *
 * @group SMS Framework
 * @coversDefaultClass \Drupal\sms_user\AccountRegistration
 */
class SmsFrameworkUserAccountRegistrationServiceTest extends SmsFrameworkKernelBase {

  use AssertMailTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'sms',
    'sms_user',
    'sms_test_gateway',
    'user',
    'telephone',
    'dynamic_entity_reference',
    'field',
  ];

  /**
   * The account registration service.
   *
   * @var \Drupal\sms_user\AccountRegistrationInterface
   */
  protected $accountRegistration;

  /**
   * The default SMS provider.
   *
   * @var \Drupal\sms\Provider\SmsProviderInterface
   */
  protected $smsProvider;

  /**
   * A memory gateway.
   *
   * @var \Drupal\sms\Entity\SmsGatewayInterface
   */
  protected $gateway;

  /**
   * A phone field ofr testing.
   *
   * @var \Drupal\field\FieldStorageConfigInterface
   */
  protected $phoneField;

  /**
   * Phone number settings for user entity type.
   *
   * @var \Drupal\sms\Entity\PhoneNumberSettingsInterface
   */
  protected $phoneNumberSettings;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installConfig('sms_user');

    $this->accountRegistration = $this->container->get('sms_user.account_registration');
    $this->smsProvider = $this->container->get('sms.provider');

    $this->gateway = $this->createMemoryGateway(['skip_queue' => TRUE]);
    $this->setFallbackGateway($this->gateway);

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

    $this->config('system.mail')
      ->set('interface.default', 'test_mail_collector')
      ->save();

    $this->config('user.settings')
      ->set('notify.register_no_approval_required', TRUE)
      ->save();
  }

  /**
   * Ensure incoming SMS does not create messages or users.
   */
  public function testUnrecognisedOffNoCreateUser() {
    $this->config('sms_user.settings')
      ->set('account_registration.unrecognized_sender.status', 0)
      ->set('account_registration.unrecognized_sender.reply.status', 1)
      ->save();

    $this->sendIncomingMessage('+123', $this->randomString());
    $this->assertEquals(0, count($this->getTestMessages($this->gateway)), 'No messages were created');
    $this->assertEquals(0, $this->countUsers(), 'No users exist.');
  }

  /**
   * Test user is created if a unrecognised phone number is used as sender.
   */
  public function testUnrecognisedCreateUser() {
    $this->config('sms_user.settings')
      ->set('account_registration.unrecognized_sender.status', 1)
      ->set('account_registration.unrecognized_sender.reply.status', 1)
      ->save();

    $sender_number = '+123123123';
    $this->sendIncomingMessage($sender_number, $this->randomString());

    $user = $this->getLastUser();
    $this->assertTrue($user instanceof UserInterface, 'One user created.');
    $this->assertEquals($sender_number, $user->{$this->phoneField->getName()}->value, 'Phone number associated');
  }

  /**
   * Test a user is not created if the sender phone number is already used.
   */
  public function testUnrecognisedCreateUserPhoneNumberRecognised() {
    $this->config('sms_user.settings')
      ->set('account_registration.unrecognized_sender.status', 1)
      ->set('account_registration.unrecognized_sender.reply.status', 1)
      ->save();

    $sender_number = '+123123123';
    $this->createEntityWithPhoneNumber($this->phoneNumberSettings, [$sender_number]);
    $this->resetTestMessages();

    $this->assertEquals(1, $this->countUsers());
    $this->sendIncomingMessage($sender_number, $this->randomString());
    $this->assertEquals(1, $this->countUsers());
    $this->assertEquals(0, count($this->getTestMessages($this->gateway)));
  }

  /**
   * Ensure no reply sent if turned off.
   */
  public function testUnrecognisedNoReply() {
    $reply_message = $this->randomString();
    $this->config('sms_user.settings')
      ->set('account_registration.unrecognized_sender.status', TRUE)
      ->set('account_registration.unrecognized_sender.reply.status', FALSE)
      ->set('account_registration.unrecognized_sender.reply.message', $reply_message)
      ->save();

    $this->sendIncomingMessage('+123123123', $this->randomString());
    $this->assertEquals(1, $this->countUsers(), 'User created');
    $this->assertFalse($this->inTestMessages($this->gateway, $reply_message));
  }

  /**
   * Ensure reply sent if turned on.
   */
  public function testUnrecognisedGotReply() {
    $reply_message = $this->randomString();
    $this->config('sms_user.settings')
      ->set('account_registration.unrecognized_sender.status', TRUE)
      ->set('account_registration.unrecognized_sender.reply.status', TRUE)
      ->set('account_registration.unrecognized_sender.reply.message', $reply_message)
      ->save();

    $this->sendIncomingMessage('+123123123', $this->randomString());
    $this->assertEquals(1, $this->countUsers(), 'User created');
    $this->assertTrue($this->inTestMessages($this->gateway, $reply_message));
  }

  /**
   * Test if a user is created despite no email address.
   */
  public function testUnrecognisedNoEmail() {
    $this->config('sms_user.settings')
      ->set('account_registration.unrecognized_sender.status', 1)
      ->save();

    $this->assertEquals(0, $this->countUsers());
    $this->sendIncomingMessage('+123123123', $this->randomString());
    $this->assertFalse(empty($this->getLastUser()->getAccountName()));
    $this->assertTrue(empty($this->getLastUser()->getEmail()));
  }

  /**
   * Test user is created from a incoming pattern message.
   */
  public function testIncomingPatternUserCreated() {
    $this->config('sms_user.settings')
      ->set('account_registration.incoming_pattern.status', 1)
      ->set('account_registration.incoming_pattern.reply.status', 1)
      ->save();

    $username = $this->randomMachineName();
    $email = 'email@email.com';
    $sender_number = '+123123123';
    $message = "E " . $email . "\nU " . $username;
    $this->sendIncomingMessage($sender_number, $message);

    $user = user_load_by_name($username);
    $this->assertTrue($user instanceof UserInterface, 'User was created');
    $this->assertEquals($username, $user->getAccountName());
    $this->assertEquals($email, $user->getEmail());
    $this->assertEquals($sender_number, $user->{$this->phoneField->getName()}->value, 'Phone number associated');
  }

  /**
   * Test all placeholders make their way into the user object.
   */
  public function testIncomingPatternPlaceholders() {
    $this->config('sms_user.settings')
      ->set('account_registration.incoming_pattern.status', 1)
      ->set('account_registration.incoming_pattern.reply.status', 1)
      ->set('account_registration.incoming_pattern.incoming_messages.0', "[email] [username] [password]")
      ->save();

    $email = 'email@domain.tld';
    $username = $this->randomMachineName();
    $password = $this->randomMachineName();

    $message = "$email $username $password";
    $this->sendIncomingMessage('+123123123', $message);

    $user = $this->getLastUser();
    $this->assertEquals($email, $user->getEmail());
    $this->assertEquals($username, $user->getAccountName());

    // Ensure password is correct:
    /** @var \Drupal\user\UserAuthInterface $userAuth */
    $userAuth = \Drupal::service('user.auth');
    $this->assertNotFalse($userAuth->authenticate($username, $password));
  }

  /**
   * Test if a duplicated placeholder is confirmed.
   */
  public function testIncomingPatternMultiplePlaceholderSuccess() {
    $this->config('sms_user.settings')
      ->set('account_registration.incoming_pattern.status', 1)
      ->set('account_registration.incoming_pattern.reply.status', 1)
      ->set('account_registration.incoming_pattern.incoming_messages.0', "[password] [username] [password]")
      ->save();

    $username = $this->randomMachineName();
    $password = $this->randomMachineName();

    $message = "$password $username $password";
    $this->sendIncomingMessage('+123123123', $message);
  }

  /**
   * Test if a duplicated placeholder is not confirmed.
   */
  public function testIncomingPatternMultiplePlaceholderFailure() {
    $this->config('sms_user.settings')
      ->set('account_registration.incoming_pattern.status', 1)
      ->set('account_registration.incoming_pattern.reply.status', 1)
      ->set('account_registration.incoming_pattern.incoming_messages.0', "[password] [username] [password]")
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
   */
  public function testIncomingPatternNoEmail() {
    $this->config('sms_user.settings')
      ->set('account_registration.incoming_pattern.status', 1)
      ->set('account_registration.incoming_pattern.incoming_messages.0', "[username] [password]")
      ->save();

    $this->assertEquals(0, $this->countUsers());

    $username = $this->randomMachineName();
    $message = "$username " . $this->randomMachineName();
    $this->sendIncomingMessage('+123123123', $message);
    $this->assertEquals($username, $this->getLastUser()->getAccountName());
    $this->assertTrue(empty($this->getLastUser()->getEmail()));
  }

  /**
   * Test if a user is created despite no placeholders.
   */
  public function testIncomingPatternNoPlaceholders() {
    $incoming_message = $this->randomString();
    $this->config('sms_user.settings')
      ->set('account_registration.incoming_pattern.status', 1)
      ->set('account_registration.incoming_pattern.incoming_messages.0', $incoming_message)
      ->set('account_registration.incoming_pattern.reply.status', TRUE)
      ->set('account_registration.incoming_pattern.reply.message', '[user:account-name] Foo [user:mail]')
      ->save();

    $this->assertEquals(0, $this->countUsers());
    $this->sendIncomingMessage('+123123123', $incoming_message);
    $this->assertEquals(1, $this->countUsers());

    // Check reply contains randomly generated username, and empty email token.
    $user = $this->getLastUser();
    $reply_message = $user->getAccountName() . ' Foo ' . $user->getEmail();
    $reply = $this->getLastTestMessage($this->gateway);
    $this->assertEquals($reply_message, $reply->getMessage());
  }

  /**
   * Ensure escaped delimiters.
   */
  public function testIncomingPatternPlaceholderEscapedDelimiters() {
    // AccountRegistration::createAccount uses '/' delimiters. Ensure that they
    // are escaped otherwise a "preg_match_all(): Unknown modifier error" will
    // be thrown.
    $incoming_message = $this->randomString() . '/';
    $this->config('sms_user.settings')
      ->set('account_registration.incoming_pattern.status', 1)
      ->set('account_registration.incoming_pattern.incoming_messages.0', $incoming_message)
      ->save();

    $this->sendIncomingMessage('+123123123', $this->randomString());
  }

  /**
   * Ensure no reply sent if turned off.
   */
  public function testIncomingPatternNoReply() {
    $reply_message = $this->randomString();
    $this->config('sms_user.settings')
      ->set('account_registration.incoming_pattern.status', TRUE)
      ->set('account_registration.incoming_pattern.incoming_messages.0', "[username] [password]")
      ->set('account_registration.incoming_pattern.reply.status', FALSE)
      ->set('account_registration.incoming_pattern.reply.message', $reply_message)
      ->save();

    $incoming_message = $this->randomMachineName() . ' ' . $this->randomMachineName();
    $this->sendIncomingMessage('+123123123', $incoming_message);
    $this->assertEquals(1, $this->countUsers(), 'User created');
    $this->assertFalse($this->inTestMessages($this->gateway, $reply_message));
  }

  /**
   * Ensure reply sent if turned on.
   */
  public function testIncomingPatternHasReply() {
    $reply_message = $this->randomString();
    $this->config('sms_user.settings')
      ->set('account_registration.incoming_pattern.status', TRUE)
      ->set('account_registration.incoming_pattern.incoming_messages.0', "[username] [password]")
      ->set('account_registration.incoming_pattern.reply.status', TRUE)
      ->set('account_registration.incoming_pattern.reply.message', $reply_message)
      ->save();

    $incoming_message = $this->randomMachineName() . ' ' . $this->randomMachineName();
    $this->sendIncomingMessage('+123123123', $incoming_message);
    $this->assertEquals(1, $this->countUsers(), 'User created');
    $this->assertTrue($this->inTestMessages($this->gateway, $reply_message));
  }

  /**
   * Ensure account activation email sent.
   */
  public function testIncomingPatternActivateEmail() {
    $this->config('sms_user.settings')
      ->set('account_registration.incoming_pattern.status', TRUE)
      ->set('account_registration.incoming_pattern.incoming_messages.0', "E [email] U [username]")
      ->set('account_registration.incoming_pattern.send_activation_email', TRUE)
      ->save();

    $subject = $this->randomMachineName();
    $this->config('user.mail')
      ->set('register_no_approval_required.subject', $subject)
      ->set('register_no_approval_required.body', 'Foo [user:display-name] Bar')
      ->save();

    $email = 'email@domain.tld';
    $username = $this->randomMachineName();
    $this->sendIncomingMessage('+123123123', 'E ' . $email . ' U ' . $username);

    $emails = $this->getMails();
    $this->assertEquals(1, count($emails), 'One email was sent.');
    $this->assertMailString('to', $email, 1);
    $this->assertMailString('subject', $subject, 1);
    $this->assertMailString('body', 'Foo ' . $username . ' Bar', 1);
  }

  /**
   * Ensure no activation email sent.
   */
  public function testIncomingPatternNoActivateEmail() {
    $this->config('sms_user.settings')
      ->set('account_registration.incoming_pattern.status', TRUE)
      ->set('account_registration.incoming_pattern.incoming_messages.0', "E [email] P [password]")
      ->set('account_registration.incoming_pattern.send_activation_email', TRUE)
      ->save();

    $this->config('user.mail')
      ->set('register_no_approval_required.subject', $this->randomMachineName())
      ->set('register_no_approval_required.body', $this->randomMachineName())
      ->save();

    $email = 'email@domain.tld';
    $password = $this->randomMachineName();
    $this->sendIncomingMessage('+123123123', 'E ' . $email . ' P ' . $password);

    $emails = $this->getMails();
    $this->assertEquals(0, count($emails), 'Zero emails sent because incoming message contained password.');
  }

  /**
   * Test error builder.
   *
   * @covers ::buildError
   */
  public function testErrorBuilder() {
    $failure_prefix = 'foo: ';
    $this->config('sms_user.settings')
      ->set('account_registration.incoming_pattern.status', 1)
      ->set('account_registration.incoming_pattern.incoming_messages.0', "[username] [email]")
      ->set('account_registration.incoming_pattern.reply.status', 1)
      ->set('account_registration.incoming_pattern.reply.message_failure', $failure_prefix . '[error]')
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
      ->set('account_registration.unrecognized_sender.status', 1)
      ->save();

    $this->sendIncomingMessage('+123123123', $this->randomString());
    $user1 = $this->getLastUser();
    $this->sendIncomingMessage('+456456456', $this->randomString());
    $user2 = $this->getLastUser();

    $this->assertNotEquals($user1->getAccountName(), $user2->getAccountName());
  }

  /**
   * Ensure non-global tokens and [user:password] are replaced in reply message.
   */
  public function testReplyTokens() {
    $this->config('sms_user.settings')
      ->set('account_registration.incoming_pattern.status', TRUE)
      ->set('account_registration.incoming_pattern.incoming_messages.0', "[username] [password]")
      ->set('account_registration.incoming_pattern.reply.status', TRUE)
      ->set('account_registration.incoming_pattern.reply.message', 'Username is [user:account-name] Password is [user:password]')
      ->save();

    $username = $this->randomMachineName();
    $password = $this->randomMachineName();
    $incoming_message = $username . ' ' . $password;
    $this->sendIncomingMessage('+123123123', $incoming_message);

    $reply_message = 'Username is ' . $username . ' Password is ' . $password;
    $this->assertTrue($this->inTestMessages($this->gateway, $reply_message));
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
      ->setDirection(Direction::INCOMING)
      ->setMessage($message)
      ->addRecipients($this->randomPhoneNumbers(1))
      ->setGateway($this->gateway);
    $incoming->setResult($this->createMessageResult($incoming));
    $this->smsProvider->queue($incoming);
  }

  /**
   * Count number of registered users.
   *
   * @return int
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

  /**
   * Check if the message body can be found in the test message memory buffer.
   *
   * @param \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway
   *   A gateway plugin instance.
   * @param string $message
   *   The message to check.
   *
   * @return bool
   *   Whether message was found in any memory messages.
   */
  public function inTestMessages(SmsGatewayInterface $sms_gateway, $message) {
    foreach ($this->getTestMessages($sms_gateway) as $sms_message) {
      if ($sms_message->getMessage() == $message) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
