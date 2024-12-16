<?php

declare(strict_types=1);

namespace Drupal\sms_user;

use Drupal\Component\Utility\Random;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\Core\Utility\Token;
use Drupal\sms\Direction;
use Drupal\sms\Entity\PhoneNumberSettingsInterface;
use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Provider\PhoneNumberVerificationInterface;
use Drupal\sms\Provider\SmsProviderInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\user\UserNameValidator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Defines the account registration service.
 */
final class AccountRegistration implements AccountRegistrationInterface, LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * Phone number settings for user.user bundle.
   */
  protected ?PhoneNumberSettingsInterface $userPhoneNumberSettings;

  /**
   * Constructs a AccountRegistration object.
   */
  final public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly Token $token,
    private readonly SmsProviderInterface $smsProvider,
    private readonly PhoneNumberVerificationInterface $phoneNumberVerificationProvider,
    private readonly UserNameValidator $userNameValidator,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly PasswordGeneratorInterface $passwordGenerator,
  ) {
  }

  public function createAccount(SmsMessageInterface $sms_message): void {
    $this->userPhoneNumberSettings = $this->phoneNumberVerificationProvider
      ->getPhoneNumberSettings('user', 'user');
    if ($this->userPhoneNumberSettings === NULL) {
      // Can't do anything if there is no phone number settings for user.
      return;
    }

    $sender_number = $sms_message->getSenderNumber();
    if ($sender_number !== NULL && \strlen($sender_number) > 0) {
      // Any users with this phone number?
      $entities = $this->phoneNumberVerificationProvider
        ->getPhoneVerificationByPhoneNumber($sender_number, NULL, 'user');
      if ($entities === []) {
        if (TRUE === $this->settings('unrecognized_sender.status')) {
          $this->allUnknownNumbers($sms_message);
        }
        if (TRUE === $this->settings('incoming_pattern.status')) {
          $this->incomingPatternMessage($sms_message);
        }
      }
    }
  }

  /**
   * Process incoming message and create a user if the phone number is unknown.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms_message
   *   An incoming SMS message.
   */
  protected function allUnknownNumbers(SmsMessageInterface $sms_message): void {
    $user = User::create(['name' => $this->generateUniqueUsername()]);
    $user->activate();

    // Sender phone number.
    $sender_number = $sms_message->getSenderNumber();
    $t_args = [];
    $t_args['%sender_phone_number'] = $sender_number;
    $phone_field_name = $this->userPhoneNumberSettings
      ->getFieldName('phone_number');
    $user->{$phone_field_name}[] = $sender_number;

    // Password.
    $password = $this->passwordGenerator->generate();
    $user->setPassword($password);

    $validate = $this->removeAcceptableViolations($user->validate());
    if ($validate->count() == 0) {
      $user->save();

      // @todo autoconfirm the number?
      // @see https://www.drupal.org/node/2709911
      $t_args['%name'] = $user->label();
      $t_args['%uid'] = $user->id();
      $this->logger?->info('Creating new account for %sender_phone_number. Username: %name. User ID: %uid', $t_args);

      // Optionally send a reply.
      $replyStatus = (bool) $this->settings('unrecognized_sender.reply.status');
      if ($sender_number !== NULL && $replyStatus) {
        /** @var string $message */
        $message = $this->settings('unrecognized_sender.reply.message');
        $message = \str_replace('[user:password]', $password, $message);
        $this->sendReply($sender_number, $user, $message);
      }
    }
    else {
      $t_args['@error'] = $this->buildError($validate);
      $this->logger?->error('Could not create new account for %sender_phone_number because there was a problem with validation: @error', $t_args);
    }
  }

  /**
   * Creates a user if an incoming message contents matches a pattern.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms_message
   *   An incoming SMS message.
   */
  protected function incomingPatternMessage(SmsMessageInterface $sms_message): void {
    $incoming_form = (string) $this->settings('incoming_pattern.incoming_messages.0');
    if ($incoming_form === '') {
      return;
    }

    $incoming_form = \str_replace("\r\n", "\n", $incoming_form);
    $compiled = $this->compileFormRegex($incoming_form, '/');
    $matches = [];
    if (\preg_match_all('/^' . $compiled . '$/', $sms_message->getMessage(), $matches)) {
      $contains_email = \strpos($incoming_form, '[email]') !== FALSE;
      $contains_username = \strpos($incoming_form, '[username]') !== FALSE;
      $contains_password = \strpos($incoming_form, '[password]') !== FALSE;

      $username = (!empty($matches['username'][0]) && $contains_username) ? $matches['username'][0] : $this->generateUniqueUsername();
      $user = User::create(['name' => $username]);
      $user->activate();

      // Sender phone number.
      $sender_number = $sms_message->getSenderNumber();
      $t_args = [];
      $t_args['%sender_phone_number'] = $sender_number;

      // Sender phone number.
      $phone_field_name = $this->userPhoneNumberSettings
        ->getFieldName('phone_number');
      $user->{$phone_field_name}[] = $sender_number;

      if (!empty($matches['email'][0]) && $contains_email) {
        $user->setEmail($matches['email'][0]);
      }

      $password = (!empty($matches['password'][0]) && $contains_password) ? $matches['password'][0] : $this->passwordGenerator->generate();
      $user->setPassword($password);

      $validate = $this->removeAcceptableViolations($user->validate(), $incoming_form);
      if ($validate->count() == 0) {
        $user->save();

        // @todo autoconfirm the number?
        // @see https://www.drupal.org/node/2709911
        /** @var string $message */
        $message = $this->settings('incoming_pattern.reply.message');
        $message = \str_replace('[user:password]', $password, $message);

        $this->logger?->info('Creating new account for %sender_phone_number. Username: %name. User ID: %uid', $t_args + [
          '%uid' => $user->id(),
          '%name' => $user->label(),
        ]);

        // Send an activation email if no password placeholder is found.
        $send_activation_email = (bool) $this->settings('incoming_pattern.send_activation_email');
        if (!$contains_password && $send_activation_email) {
          \_user_mail_notify('register_no_approval_required', $user);
        }
      }
      else {
        /** @var string $message */
        $message = $this->settings('incoming_pattern.reply.message_failure');

        $error = $this->buildError($validate);
        $message = \str_replace('[error]', $error, $message);

        $this->logger?->warning('Could not create new account for %sender_phone_number because there was a problem with validation: @error', $t_args + [
          '@error' => $error,
        ]);
      }

      // Optionally send a reply.
      if ($sender_number !== NULL) {
        $replyStatus = (bool) $this->settings('incoming_pattern.reply.status');
        if ($replyStatus) {
          $this->sendReply($sender_number, $user, $message);
        }
      }
    }
  }

  /**
   * Send a reply message to the sender of a message.
   *
   * @param string $sender_number
   *   Phone number of sender of incoming message. And if a user was created,
   *   this number was used.
   * @param \Drupal\user\UserInterface $user
   *   A user account. The account may not be saved.
   * @param string $message
   *   Message to send as a reply.
   */
  protected function sendReply($sender_number, UserInterface $user, $message): void {
    $sms_message = SmsMessage::create();
    $sms_message
      ->addRecipient($sender_number)
      ->setDirection(Direction::OUTGOING);

    $data = [];
    $data['sms-message'] = $sms_message;
    $data['user'] = $user;
    $sms_message->setMessage($this->token->replace($message, $data));

    // Use queue(), instead of phone number provider sendMessage()
    // because the phone number is not confirmed.
    try {
      $this->smsProvider->queue($sms_message);
    }
    catch (\Exception $e) {
      $t_args = [];
      $t_args['%recipient'] = $sender_number;
      $t_args['%error'] = $e->getMessage();
      $this->logger?->warning('Reply message could not be sent to recipient %recipient: %error', $t_args);
    }
  }

  /**
   * Compile incoming form configuration to a regular expression.
   *
   * @param string $form_string
   *   A incoming form configuration message.
   * @param string $delimiter
   *   The delimiter to escape, as used by preg_match*().
   *
   * @return string
   *   A regular expression.
   */
  protected function compileFormRegex($form_string, $delimiter): string {
    $placeholders = ['username' => '.+', 'email' => '\S+', 'password' => '.+'];

    // Placeholders enclosed in square brackets and escaped for use in regular
    // expressions. \[user\] , \[email\] etc.
    $regex_placeholders = [];
    foreach (\array_keys($placeholders) as $d) {
      $regex_placeholders[] = \preg_quote('[' . $d . ']');
    }

    // Split message so placeholders are separated from other text.
    // e.g. for 'U [username] P [password], splits to:
    // 'U ', '[username]', ' P ', '[password]'.
    $regex = '/(' . \implode('|', $regex_placeholders) . '+)/';
    $words = \preg_split($regex, $form_string, -1, PREG_SPLIT_DELIM_CAPTURE);

    // Track if a placeholder was used, so subsequent usages create a named back
    // reference. This allows you to use placeholders more than once as a form
    // of confirmation. e.g: 'U [username] P [password] [password]'.
    $placeholder_usage = [];

    $compiled = '';
    foreach ($words as $word) {
      // Remove square brackets from word to determine if it is a placeholder.
      $placeholder = \mb_substr($word, 1, -1);

      // Determine if word is a placeholder.
      if (isset($placeholders[$placeholder])) {
        $placeholder_regex = $placeholders[$placeholder];
        if (!\in_array($placeholder, $placeholder_usage)) {
          // Convert placeholder to a capture group.
          $compiled .= '(?<' . $placeholder . '>' . $placeholder_regex . ')';
          $placeholder_usage[] = $placeholder;
        }
        else {
          // Create a back reference to the previous named capture group.
          $compiled .= '\k{' . $placeholder . '}';
        }
      }
      else {
        // Text is not a placeholder, do not convert to a capture group.
        $compiled .= \preg_quote($word, $delimiter);
      }
    }

    return $compiled;
  }

  /**
   * Build an error string from a constraint violation list.
   *
   * @param \Symfony\Component\Validator\ConstraintViolationListInterface $violations
   *   A constraint violation list.
   *
   * @return string
   *   Violation errors joined together.
   */
  protected function buildError(ConstraintViolationListInterface $violations): string {
    $error = '';
    foreach ($violations as $violation) {
      $error .= (string) $violation->getMessage() . " ";
    }
    return \strip_tags($error);
  }

  /**
   * Generate a unique user name that is not being used.
   *
   * @return string
   *   A unique user name.
   */
  protected function generateUniqueUsername(): string {
    $random = new Random();
    do {
      $userName = $random->name(8, TRUE);
    } while (
      \count($this->userNameValidator->validateName($userName)) > 0 ||
      $this->userNameExists($userName)
    );
    return $userName;
  }

  private function userNameExists(string $userName): bool {
    return $this->entityTypeManager
      ->getStorage('user')
      ->loadByProperties(['name' => $userName]) !== [];
  }

  /**
   * Filter out acceptable validation errors.
   *
   * @param \Drupal\Core\Entity\EntityConstraintViolationListInterface $violations
   *   A violation list.
   * @param string|null $incoming_form
   *   Incoming form, if applicable.
   *
   * @return \Drupal\Core\Entity\EntityConstraintViolationListInterface
   *   A filtered violation list.
   */
  protected function removeAcceptableViolations(EntityConstraintViolationListInterface $violations, $incoming_form = NULL): EntityConstraintViolationListInterface {
    // 'mail' will not fail validation if current user has 'administer users'.
    $needs_email = isset($incoming_form) && (\strpos($incoming_form, '[email]') !== FALSE);
    if (!$needs_email) {
      // Invalid email field is acceptable if it is not required.
      foreach ($violations as $offset => $violation) {
        if ($violation->getPropertyPath() == 'mail') {
          $violations->remove($offset);
        }
      }
    }

    return $violations;
  }

  /**
   * Get the account_registration configuration.
   *
   * @param string $name
   *   The configuration name.
   */
  protected function settings(string $name): mixed {
    return $this->configFactory
      ->get('sms_user.settings')
      ->get('account_registration.' . $name);
  }

}
