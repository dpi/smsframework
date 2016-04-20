<?php

/**
 * @file
 * Contains \Drupal\sms_user\AccountRegistration.
 */

namespace Drupal\sms_user;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Utility\Token;
use Drupal\sms\Provider\SmsProviderInterface;
use Drupal\sms\Entity\PhoneNumberSettings;
use Drupal\sms\Provider\PhoneNumberProviderInterface;
use Drupal\sms\Entity\SmsMessageInterface;
use Drupal\user\Entity\User;
use Drupal\Component\Utility\Random;
use Drupal\sms\Entity\SmsMessage;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Defines the account registration service.
 */
class AccountRegistration implements AccountRegistrationInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface;
   */
  protected $configFactory;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The SMS provider.
   *
   * @var \Drupal\sms\Provider\SmsProviderInterface
   */
  protected $smsProvider;

  /**
   * Phone number provider.
   *
   * @var \Drupal\sms\Provider\PhoneNumberProviderInterface
   */
  protected $phoneNumberProvider;

  /**
   * Phone number settings for user.user bundle.
   *
   * @var \Drupal\sms\Entity\PhoneNumberSettingsInterface|NULL
   */
  protected $userPhoneNumberSettings;

  /**
   * Constructs a AccountRegistration object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Utility\Token $token
   *   The token replacement system.
   * @param \Drupal\sms\Provider\SmsProviderInterface $sms_provider
   *   The SMS provider.
   * @param \Drupal\sms\Provider\PhoneNumberProviderInterface $phone_number_provider
   *   The phone number provider.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Token $token, SmsProviderInterface $sms_provider, PhoneNumberProviderInterface $phone_number_provider) {
    $this->configFactory = $config_factory;
    $this->token = $token;
    $this->smsProvider = $sms_provider;
    $this->phoneNumberProvider = $phone_number_provider;

    // @fixme, use phone number provider to get settings.
    // @see https://www.drupal.org/node/2709465
//      $phone_number_settings = $this->phoneNumberProvider->getPhoneNumberSettings('user', 'usxer');
    $this->userPhoneNumberSettings = PhoneNumberSettings::load('user.user');

    // @fixme.  number resolution should move to a method on
    // @see https://www.drupal.org/node/2709463
    $this->phoneNumberVerificationStorage = \Drupal::entityTypeManager()
      ->getStorage('sms_phone_number_verification');

    $this->settings = $this->configFactory
      ->get('sms_user.settings')
      ->get('account_registration');
  }

  /**
   * @inheritdoc
   */
  public function createAccount(SmsMessageInterface $sms_message) {
    $sender_number = $sms_message->getSenderNumber();
    if (!empty($sender_number)) {
      // Any users with this phone number?
      $count = $this->phoneNumberVerificationStorage
        ->getQuery()
        ->condition('entity__target_type', 'user')
        ->condition('phone', $sender_number)
        ->count()
        ->execute();

      if (!$count) {
        if (!empty($this->settings['all_unknown_numbers']['status'])) {
          $this->allUnknownNumbers($sms_message);
        }
        if (!empty($this->settings['formatted']['status'])) {
          $this->preFormattedMessage($sms_message);
        }
      }
    }
  }

  /**
   * Process an incoming message and create a user if the phone number is
   * unrecognised.
   *
   * @param \Drupal\sms\Entity\SmsMessageInterface $sms_message
   *   An incoming SMS message.
   */
  protected function allUnknownNumbers(SmsMessageInterface $sms_message) {
    /** @var \Drupal\user\UserInterface $user */
    $user = User::create(['name' => $this->generateUniqueUsername()]);
    $user->activate();

    // Sender phone number.
    $sender_number = $sms_message->getSenderNumber();
    $t_args['%sender_phone_number'] = $sender_number;
    $phone_field_name = $this->userPhoneNumberSettings->getFieldName('phone_number');
    $user->{$phone_field_name}[] = $sender_number;

    // Password.
    $password = user_password();
    $user->setPassword($password);

    $validate = $user->validate();
    if ($validate->count() == 0) {
      $user->save();

      // @todo autoconfirm the number?
      // @see https://www.drupal.org/node/2709911

      $t_args['%name'] = $user->label();
      $t_args['%uid'] = $user->id();
      \Drupal::logger('sms_user.account_registration.all_unknown_numbers')
        ->info('Creating new account for %sender_phone_number. Username: %name. User ID: %uid', $t_args);

      // Optionally send a reply.
      if (!empty($this->settings['all_unknown_numbers']['reply']['status'])) {
        $message = $this->settings['all_unknown_numbers']['reply']['message'];
        $message = str_replace('[user:password]', $password, $message);
        $this->sendReply($sender_number, $user, $message);
      }
    }
    else {
      $t_args['@error'] = $this->buildError($validate);
      \Drupal::logger('sms_user.account_registration.all_unknown_numbers')
        ->error('Could not create new account for %sender_phone_number because there was a problem with validation: @error', $t_args);
    }
  }

  /**
   * Process an incoming message and create a user if the message matches
   * the incoming message format.
   *
   * @param \Drupal\sms\Entity\SmsMessageInterface $sms_message
   *   An incoming SMS message.
   */
  protected function preFormattedMessage(SmsMessageInterface $sms_message) {
    if (!empty($this->settings['formatted']['incoming_messages'][0])) {
      $incoming_form = $this->settings['formatted']['incoming_messages'][0];
      $incoming_form = str_replace("\r\n", "\n", $incoming_form);
      $compiled = $this->compileFormRegex($incoming_form);
      $matches = [];
      if (preg_match_all('/^' . $compiled . '$/', $sms_message->getMessage(), $matches)) {
        $contains_email = strpos($incoming_form, '[email]') !== FALSE;
        $contains_username = strpos($incoming_form, '[username]') !== FALSE;
        $contains_password = strpos($incoming_form, '[password]') !== FALSE;

        $username = (!empty($matches['username'][0]) && $contains_username) ? $matches['username'][0] : $this->generateUniqueUsername();
        /** @var \Drupal\user\UserInterface $user */
        $user = User::create(['name' => $username]);
        $user->activate();

        // Sender phone number.
        $sender_number = $sms_message->getSenderNumber();
        $t_args['%sender_phone_number'] = $sender_number;

        // Sender phone number.
        $phone_field_name = $this->userPhoneNumberSettings->getFieldName('phone_number');
        $user->{$phone_field_name}[] = $sender_number;

        if (!empty($matches['email'][0]) && $contains_email) {
          $user->setEmail($matches['email'][0]);
        }

        $password = (!empty($matches['password'][0]) && $contains_password) ? $matches['password'][0] : user_password();
        $user->setPassword($password);

        $validate = $user->validate();
        if ($validate->count() == 0) {
          $user->save();

          // @todo autoconfirm the number?
          // @see https://www.drupal.org/node/2709911

          $message = $this->settings['formatted']['reply']['message'];
          $message = str_replace('[user:password]', $password, $message);

          \Drupal::logger('sms_user.account_registration.formatted')
            ->info('Creating new account for %sender_phone_number. Username: %name. User ID: %uid', $t_args + [
              '%uid' => $user->id(),
              '%name' => $user->label(),
            ]);

          // Send an activation email if no password placeholder is found.
          if (!$contains_password && !empty($this->settings['formatted']['activation_email'])) {
            _user_mail_notify('register_no_approval_required', $user);
          }
        }
        else {
          $message = $this->settings['formatted']['reply']['message_failure'];

          $error = $this->buildError($validate);
          $message = str_replace('[error]', strip_tags($error), $message);

          \Drupal::logger('sms_user.account_registration.formatted')
            ->warning('Could not create new account for %sender_phone_number because there was a problem with validation: @error', $t_args + [
              '@error' => $error,
            ]);
        }

        // Optionally send a reply.
        if (!empty($this->settings['formatted']['reply']['status'])) {
          $this->sendReply($sender_number, $user, $message);
        }
      }
    }
  }

  /**
   * Send a reply message to the sender of a message.
   *
   * @param $sender_number
   *   Phone number of sender of incoming message. And if a user was created,
   *   this number was used.
   * @param $user
   *   A user account. The account may not be saved.
   * @param $message
   *   Message to send as a reply.
   */
  protected function sendReply($sender_number, $user, $message) {
    /** @var \Drupal\sms\Entity\SmsMessageInterface $sms_message */
    $sms_message = SmsMessage::create();
    $sms_message
      ->addRecipient($sender_number)
      ->setDirection(SmsMessageInterface::DIRECTION_OUTGOING);

    $data['sms-message'] = $sms_message;
    $data['user'] = $user;
    $sms_message->setMessage($this->token->replace($message, $data));

    // Use queue(), instead of phone number provider sendMessage()
    // because the phone number is not confirmed.
    $this->smsProvider
      ->queue($sms_message);
  }

  /**
   * Compile incoming form configuration to a regular expression.
   *
   * @param string $form_string
   *   A incoming form configuration message.
   *
   * @return string
   *   A regular expression.
   */
  protected function compileFormRegex($form_string) {
    $placeholders = ['username' => '.+', 'email' => '\S+', 'password' => '.+'];

    // Placeholders enclosed in square brackets and escaped for use in regular
    // expressions. \[user\] , \[email\] etc.
    $regex_placeholders = [];
    foreach (array_keys($placeholders) as $d) {
      $regex_placeholders[] = preg_quote('[' . $d . ']');
    }

    // Split message so placeholders are separated from other text.
    // e.g. for 'U [username] P [password], splits to:
    // 'U ', '[username]', ' P ', '[password]'.
    $regex = '/(' . implode('|', $regex_placeholders) . '+)/';
    $words = preg_split($regex, $form_string, NULL, PREG_SPLIT_DELIM_CAPTURE);

    // Track if a placeholder was used, so subsequent usages create a named
    // back reference. This allows you to use placeholders more than once as a form of
    // confirmation. e.g: 'U [username] P [password] [password]'
    $placeholder_usage = [];

    $compiled = '';
    foreach ($words as $word) {
      // Remove square brackets from word to determine if it is a placeholder.
      $placeholder = mb_substr($word, 1, -1);

      // Determine if word is a placeholder.
      if (isset($placeholders[$placeholder])) {
        $placeholder_regex = $placeholders[$placeholder];
        if (!in_array($placeholder, $placeholder_usage)) {
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
        $compiled .= preg_quote($word);
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
  protected function buildError(ConstraintViolationListInterface $violations) {
    $error = '';
    foreach ($violations as $violation) {
      $error .= $violation->getPropertyPath() . ': ' . (string) $violation->getMessage() . " \n";
    }
    return $error;
  }

  /**
   * Generate a unique user name that is not being used.
   *
   * @return string
   *   A unique user name.
   */
  protected function generateUniqueUsername() {
    $random = new Random();
    do {
      $username = $random->name(8, TRUE);
    }
    while (user_validate_name($username) || user_load_by_name($username));
    return $username;
  }

}
