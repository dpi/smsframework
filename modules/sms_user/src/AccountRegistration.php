<?php

/**
 * @file
 * Contains \Drupal\sms_user\AccountRegistration.
 */

namespace Drupal\sms_user;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\sms\Provider\SmsProviderInterface;
use Drupal\sms\Entity\PhoneNumberSettings;
use Drupal\sms\Provider\PhoneNumberProviderInterface;
use Drupal\sms\Entity\SmsMessageInterface;
use Drupal\user\Entity\User;
use Drupal\Component\Utility\Random;
use Drupal\sms\Entity\SmsMessage;

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
   * @param \Drupal\sms\Provider\SmsProviderInterface $sms_provider
   *   The SMS provider.
   * @param \Drupal\sms\Provider\PhoneNumberProviderInterface $phone_number_provider
   *   The phone number provider.
   */
  public function __construct(ConfigFactoryInterface $config_factory, SmsProviderInterface $sms_provider, PhoneNumberProviderInterface $phone_number_provider) {
    $this->configFactory = $config_factory;
    $this->smsProvider = $sms_provider;
    $this->phoneNumberProvider = $phone_number_provider;

    // @fixme, use phone number provider to get settings.
    // phone number provider should return a PhoneNumberSettings obj,
//      $phone_number_settings = $this->phoneNumberProvider->getPhoneNumberSettings('user', 'usxer');
    $this->userPhoneNumberSettings = PhoneNumberSettings::load('user.user');

    // @fixme. Temporary. number resolution should move to a method on
    // phonenumberprovider.
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
    $sender_number = $sms_message->getSenderNumber();
    $t_args['%sender_phone_number'] = $sender_number;

    /** @var \Drupal\user\UserInterface $user */
    $user = User::create(['name' => $this->generateUniqueUsername()]);
    $phone_field_name = $this->userPhoneNumberSettings->getFieldName('phone_number');
    $user->{$phone_field_name}[] = $sender_number;

    $validate = $user->validate();
    if ($validate->count() == 0) {
      $user->save();
      // @todo autoconfirm the number?

      $t_args['%name'] = $user->label();
      $t_args['%uid'] = $user->id();
      \Drupal::logger('sms_user.account_registration.all_unknown_numbers')
        ->info('Creating new account for %sender_phone_number. Username: %name. User ID: %uid', $t_args);

      // Optionally send a reply.
      if (!empty($this->settings['all_unknown_numbers']['reply']['status'])) {
        $message = $this->settings['all_unknown_numbers']['reply']['message'];
        $data['sms-message'] = $sms_message;
        $data['user'] = $user;
        $message = \Drupal::token()->replace($message, $data);

        /** @var \Drupal\sms\Entity\SmsMessageInterface $reply */
        $reply = SmsMessage::create();
        $reply
          ->setMessage($message)
          ->addRecipient($sender_number)
          ->setDirection(SmsMessageInterface::DIRECTION_OUTGOING);

        // Use queue(), instead of phone number provider sendMessage()
        // because the phone number is not confirmed.
        $this->smsProvider
          ->queue($reply);
      }
    }
    else {
      $error = '';
      foreach ($validate as $e) {
        $error .= $e->getPropertyPath() . ': ' . (string)$e->getMessage() . " \n";
      }
      $t_args['@error'] = $error;
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
        $phone_field_name = $this->userPhoneNumberSettings->getFieldName('phone_number');
        $user->{$phone_field_name}[] = $sender_number;

        if (!empty($matches['email'][0]) && $contains_email) {
          $user->setEmail($matches['email'][0]);
        }

        if (!empty($matches['password'][0]) && $contains_password) {
          $user->setPassword($matches['password'][0]);
        }

        $validate = $user->validate();
        if ($validate->count() == 0) {
          $user->save();
          $t_args['%name'] = $user->label();
          $t_args['%uid'] = $user->id();
          $severity = 'info';
          $log = 'Creating new account for %sender_phone_number. Username: %name. User ID: %uid';
        }
        else {
          $error = '';
          foreach ($validate as $e) {
            $error .= $e->getPropertyPath() . ': ' . (string) $e->getMessage() . " \n";
          }
          $t_args['@error'] = $error;
          $severity = 'warning';
          $log = 'Could not create new account for %sender_phone_number because there was a problem with validation: @error';
        }

        \Drupal::logger('sms_user.account_registration.formatted')
          ->log($severity, $log, $t_args);

        // Optionally send a reply.
        if (!empty($this->settings['formatted']['reply']['status'])) {
          $message = ($validate->count() == 0) ? $this->settings['formatted']['reply']['message'] : $this->settings['formatted']['reply']['message_failure'];
          $data['sms-message'] = $sms_message;
          $data['user'] = $user;
          $message = \Drupal::token()->replace($message, $data);
          if (isset($t_args['@error'])) {
            $message = str_replace('[error]', strip_tags($t_args['@error']), $message);
          }

          /** @var \Drupal\sms\Entity\SmsMessageInterface $reply */
          $reply = SmsMessage::create();
          $reply
            ->setMessage($message)
            ->addRecipient($sender_number)
            ->setDirection(SmsMessageInterface::DIRECTION_OUTGOING);

          $this->smsProvider
            ->queue($reply);
        }
      }
    }
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
    $delimiters = ['username' => '.+', 'email' => '\S+', 'password' => '.+'];
    $regex_delimiters = [];

    foreach (array_keys($delimiters) as $d) {
      $regex_delimiters[] = preg_quote('[' . $d . ']');
    }

    $regex = '/(' . implode('|', $regex_delimiters) . '+)/';
    $words = preg_split($regex, $form_string, NULL,   PREG_SPLIT_DELIM_CAPTURE);

    $compiled = '';
    $delimiter_counter = [];
    foreach ($words as $w) {
      $trimmed = mb_substr($w, 1, -1);
      if (isset($delimiters[$trimmed])) {
        $delimiter_regex = $delimiters[$trimmed];
        if (!isset($delimiter_counter[$trimmed])) {
          $compiled .= '(?<' . $trimmed . '>' . $delimiter_regex . ')';
        }
        else {
          $compiled .= '\k{' . $trimmed . '}';
        }
        $delimiter_counter[$trimmed] = TRUE;
      }
      else {
        $compiled .= preg_quote($w);
      }
    }

    return $compiled;
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
