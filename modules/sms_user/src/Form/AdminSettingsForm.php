<?php

/**
 * @file
 * Contains AdminSettingsForm class
 */

namespace Drupal\sms_user\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Render\Element;

/**
 * Provides a general settings form for SMS User.
 */
class AdminSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'sms_user_admin_settings';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $op = NULL, $domain = NULL) {
    $form['#attached']['library'][] = 'sms_user/admin';
    $config = $this->config('sms_user.settings');
    $form['registration_form'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Show mobile fields during user registration'),
      '#description' => $this->t('Specify if the site should collect mobile information during registration.'),
      '#options' => array(
        $this->t('Disabled'),
        $this->t('Optional'),
        $this->t('Required')
      ),
      '#default_value' => $config->get('registration_form'),
    );
  
    $form['confirmation_message'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Confirmation message format'),
      '#default_value' => $config->get('confirmation_message'),
      '#description' => $this->t('Specify the format for confirmation messages. Keep this as short as possible.'),
      '#size' => 140,
      '#maxlength' => 255,
    );
  
    // Add the token help to a collapsed fieldset at the end of the configuration page.
    $form['tokens']['token_help'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Available Tokens List'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['tokens']['token_help']['content'] = array(
      '#theme' => 'token_tree',
      '#token_types' => array('sms_user'),
    );
    /*
    $form['tokens'] = array(
      '#type' => 'fieldset',
      '#title' => t('Available replacement patterns'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
  
    $form['tokens']['content']['#value'] = theme('token_tree', array('token_types' => array('sms_user')));
    */

    // Active hours.
    $form['active_hours'] = [
      '#type' => 'details',
      '#title' => $this->t('Active hours'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['active_hours']['status'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable active hours'),
      '#description' => $this->t('Active hours will suspend transmission of automated SMS messages until the users local time is between any of these hours. The site default timezone is used if a user has not selected a timezone. Active hours are not applied to SMS messages created as a result of direct user action. Messages which are already queued are not retroactively updated.'),
      '#default_value' => $config->get('active_hours.status'),
    );

    $form['active_hours']['days_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="active_hours[status]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['active_hours']['days_container']['days'] = [
      '#type' => 'table',
      '#header' => [
        'day' => $this->t('Day'),
        'start' => $this->t('Start time'),
        'end' => $this->t('End time'),
      ],
      '#parents' => ['active_hours', 'days']
    ];

    // Convert configuration into days.
    $day_defaults = [];
    foreach ($config->get('active_hours.ranges') as $range) {
      $start = new DrupalDateTime($range['start']);
      $end = new DrupalDateTime($range['end']);
      $start_day = strtolower($start->format('l'));

      $day_defaults[$start_day]['start'] = $start->format('G');

      if (new DrupalDateTime($start_day . ' +1 day') == $end) {
        $day_defaults[$start_day]['end'] = 24;
      }
      else {
        $day_defaults[$start_day]['end'] = $end->format('G');
      }
    }

    // Prepare options for select fields.
    $hours = [];
    for ($i = 0; $i < 24; $i++) {
      $hours[$i] = DrupalDateTime::datePad($i) . ':00';
    }
    $hours[0] = $this->t(' - Start of day - ');
    $end_hours = $hours;
    unset($end_hours[0]);
    $end_hours[24] = $this->t(' - End of day - ');

    $timestamp = strtotime('next Sunday');
    for ($i = 0; $i < 7; $i++) {
      $row = ['#tree' => TRUE,];
      $day = strftime('%A', $timestamp);
      $day_lower = strtolower($day);

      $row['day']['#plain_text'] = $day;

      // @todo convert to 'datetime' after
      // https://www.drupal.org/node/2703941 is fixed.
      $row['start'] = [
        '#type' => 'select',
        '#title' => $this->t('Start time for @day', ['@day' => $day]),
        '#title_display' => 'invisible',
        '#default_value' => isset($day_defaults[$day_lower]['start']) ? $day_defaults[$day_lower]['start'] : -1,
        '#options' => $hours,
        '#empty_option' => $this->t(' - Suspend messages for this day - '),
        '#empty_value' => -1,
      ];
      $row['end'] = [
        '#type' => 'select',
        '#title' => $this->t('Start time for @day', ['@day' => $day]),
        '#title_display' => 'invisible',
        '#default_value' => isset($day_defaults[$day_lower]['end']) ? $day_defaults[$day_lower]['end'] : 24,
        '#options' => $end_hours,
        '#states' => [
          'invisible' => [
            ':input[name="active_hours[days][' . $day_lower . '][start]"]' => ['value' => '-1'],
          ],
        ],
      ];

      $timestamp = strtotime('+1 day', $timestamp);
      $form['active_hours']['days_container']['days'][$day_lower] = $row;
    }

    // Account registration.
    $form['account_registration'] = [
      '#type' => 'details',
      '#title' => $this->t('Account creation'),
      '#description' => $this->t('New accounts can be created and associated with a phone number.'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    if ($config->get('account_registration.all_unknown_numbers.status')) {
      $radio_value = 'all';
    }
    else if ($config->get('account_registration.formatted.status')) {
      $radio_value = 'formatted';
    }
    else {
      $radio_value = 'none';
    }

    $form['account_registration']['behaviour'] = [
      '#type' => 'radios',
      '#options' => NULL,
      '#title' => $this->t('Account registration via SMS'),
      '#required' => TRUE,
      '#default_value' => $radio_value,
    ];

    $form['account_registration']['none']['#tree'] = TRUE;
    $form['account_registration']['none']['radio'] = [
      '#type' => 'radio',
      '#title' => $this->t('Disabled'),
      '#description' => $this->t('Disable account creation via SMS.'),
      '#return_value' => 'none',
      '#parents' => ['account_registration', 'behaviour'],
      '#default_value' => $radio_value,
    ];

    $form['account_registration']['all']['#tree'] = TRUE;
    $form['account_registration']['all']['radio'] = [
      '#type' => 'radio',
      '#title' => $this->t('All unrecognised phone numbers'),
      '#description' => $this->t('Automatically create a Drupal account for all phone numbers not associated with an existing account.'),
      '#return_value' => 'all',
      '#parents' => ['account_registration', 'behaviour'],
      '#default_value' => $radio_value,
    ];

    $form['account_registration']['all_options'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['sms_user-radio-indent'],
      ],
      '#states' => [
        'visible' => [
          ':input[name="account_registration[behaviour]"]' => ['value' => 'all'],
        ],
      ],
    ];

    $form['account_registration']['all_options']['reply_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable reply message'),
      '#default_value' => $config->get('account_registration.all_unknown_numbers.reply.status'),
    ];

    $form['account_registration']['all_options']['reply'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="account_registration[all_options][reply_status]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['account_registration']['all_options']['reply']['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Reply message'),
      '#description' => $this->t('Send a message after a new account is created.'),
      '#default_value' => $config->get('account_registration.all_unknown_numbers.reply.message'),
      '#states' => [
        'visible' => [
          ':input[name="account_registration[all_options][reply_status]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['account_registration']['all_options']['reply']['tokens'] = $this->buildTokenElement();

    $form['account_registration']['formatted']['radio'] = [
      '#type' => 'radio',
      '#title' => $this->t('Pre-formatted message'),
      '#description' => $this->t('Automatically create a Drupal account if message is received in a specified format.'),
      '#return_value' => 'formatted',
      '#parents' => ['account_registration', 'behaviour'],
      '#default_value' => $radio_value,
    ];

    $form['account_registration']['formatted_options'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['sms_user-radio-indent'],
      ],
      '#states' => [
        'visible' => [
          ':input[name="account_registration[behaviour]"]' => ['value' => 'formatted'],
        ],
      ],
    ];

    $form['account_registration']['formatted_options']['incoming_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Incoming message'),
      '#description' => $this->t('You must use at least one placeholder: [email] and/or [password]. [username] is also available. If password is omitted: a random password will be generated. If username is omitted: a random username will be generated. If email address is omitted: no email address will be associated with the account.'),
      '#default_value' => $config->get('account_registration.formatted.incoming_messages.0'),
    ];

    $form['account_registration']['formatted_options']['activation_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send activation email'),
      '#description' => $this->t('Send activation email if an [email] placeholder is present, and [password] placeholder is omitted.'),
      '#default_value' => $config->get('account_registration.formatted.activation_email'),
    ];

    $form['account_registration']['formatted_options']['reply_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable reply message'),
      '#default_value' => $config->get('account_registration.formatted.reply.status'),
    ];

    $form['account_registration']['formatted_options']['reply'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['sms_user-radio-indent'],
      ],
      '#states' => [
        'visible' => [
          ':input[name="account_registration[formatted_options][reply_status]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['account_registration']['formatted_options']['reply']['message_success'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Reply message (success)'),
      '#description' => $this->t('Send a message after a new account is successfully created.'),
      '#default_value' => $config->get('account_registration.formatted.reply.message'),
    ];

    $form['account_registration']['formatted_options']['reply']['message_failure'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Reply message (failure)'),
      '#description' => $this->t('Send a message if a new account could not be created. Such reasons include: username already taken, email already used.'),
      '#default_value' => $config->get('account_registration.formatted.reply.message_failure'),
    ];

    $form['account_registration']['formatted_options']['reply']['tokens'] = $this->buildTokenElement();

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Active hours.
    foreach ($form_state->getValue(['active_hours', 'days']) as $day => $row) {
      foreach ($row as $position => $hour) {
        if ($hour == -1) {
          $form_state->unsetValue(['active_hours', 'days', $day]);
          continue 2;
        }
        else if ($hour == 24) {
          $str = $day . ' +1 day';
        }
        else {
          $str = $day . ' ' . $hour . ':00';
        }
        $form_state->setValue(['active_hours', 'days', $day, $position], $str);
      }
    }

    // Ensure at least one enabled.
    if ($form_state->getValue(['active_hours', 'status']) && empty($form_state->getValue(['active_hours', 'days']))) {
      // Show error on all start elements.
      foreach (Element::children($form['active_hours']['days_container']['days']) as $day) {
        $form_state->setError($form['active_hours']['days_container']['days'][$day]['start'], $this->t('If active hours hours are enabled there must be at least one enabled day.'));
      }
    }

    // Ensure end times are greater than start times.
    foreach ($form_state->getValue(['active_hours', 'days']) as $day => $row) {
      $start = new DrupalDateTime($row['start']);
      $end = new DrupalDateTime($row['end']);
      if ($end < $start) {
        $form_state->setError($form['active_hours']['days_container']['days'][$day]['end'], $this->t('End time must be greater than start time.'));
      }
    }

    // Account registration.
    $account_registration = $form_state->getValue(['account_registration']);
    if (!empty($account_registration['all_options']['reply_status']) && empty($account_registration['all_options']['reply']['message'])) {
      // Reply is enabled, but empty reply.
      $form_state->setError($form['account_registration']['all_options']['reply']['message'], $this->t('Reply message must have a value if reply is enabled.'));
    }

    // Incoming message.
    $incoming_message = $account_registration['formatted_options']['incoming_message'];
    if ($account_registration['behaviour'] == 'formatted' && empty($incoming_message)) {
      // Empty incoming message.
      $form_state->setError($form['account_registration']['formatted_options']['incoming_message'], $this->t('Incoming message must be filled if using pre-formatted option.'));
    }
    else if (!empty($incoming_message)) {
      $contains_email = strpos($incoming_message, '[email]') !== FALSE;
      $contains_password = strpos($incoming_message, '[password]') !== FALSE;
      if (!$contains_email && !$contains_password) {
        // Doesn't contain either placeholder.
        $form_state->setError($form['account_registration']['formatted_options']['incoming_message'], $this->t('Incoming message must contain at least one [email] and/or [password] placeholders.'));
      }

      $activation_email = $account_registration['formatted_options']['activation_email'];
      if ($activation_email && !($contains_email && !$contains_password)) {
        // Check if password and email occur at the same time.
        $form_state->setError($form['account_registration']['formatted_options']['activation_email'], $this->t('Activation email cannot be sent if [password] placeholder is present.'));
      }

      // Make sure there is a separator between placeholders so regex capture
      // groups work correctly.
      $placeholders = ['[username]', '[email]', '[password]'];
      $regex_placeholder = [];
      foreach ($placeholders as $placeholder) {
        $regex_placeholder[] = preg_quote($placeholder);
      }

      $regex = '/(' . implode('|', $regex_placeholder) . '+)/';
      $last_word_is_placeholder = FALSE;
      foreach (preg_split($regex, $incoming_message, NULL, PREG_SPLIT_DELIM_CAPTURE) as $word) {
        if ($word === '') {
          continue;
        }
        $this_word_is_placeholder = in_array($word, $placeholders);
        if ($last_word_is_placeholder && $this_word_is_placeholder) {
          $form_state->setError($form['account_registration']['formatted_options']['incoming_message'], $this->t('There must be a separator between placeholders.'));
        }
        $last_word_is_placeholder = $this_word_is_placeholder;
      }
    }

    // Replies.
    if (!empty($account_registration['formatted_options']['reply_status']) && empty($account_registration['formatted_options']['reply']['message_success'])) {
      // Reply is enabled, but empty reply.
      $form_state->setError($form['account_registration']['formatted_options']['reply']['message_success'], $this->t('Reply message must have a value if reply is enabled.'));
    }
    if (!empty($account_registration['formatted_options']['reply_status']) && empty($account_registration['formatted_options']['reply']['message_failure'])) {
      // Reply is enabled, but empty reply.
      $form_state->setError($form['account_registration']['formatted_options']['reply']['message_failure'], $this->t('Reply message must have a value if reply is enabled.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('sms_user.settings');

    // Account Registration.
    $account_registration = $form_state->getValue('account_registration');
    $behaviour = $account_registration['behaviour'];

    $config
      ->set('account_registration.all_unknown_numbers.status', $behaviour == 'all')
      ->set('account_registration.formatted.status', $behaviour == 'formatted')
      ->set('account_registration.all_unknown_numbers.reply.status', $account_registration['all_options']['reply_status'])
      ->set('account_registration.all_unknown_numbers.reply.message', $account_registration['all_options']['reply']['message'])
      ->set('account_registration.formatted.incoming_messages.0', $account_registration['formatted_options']['incoming_message'])
      ->set('account_registration.formatted.reply.status', $account_registration['formatted_options']['reply_status'])
      ->set('account_registration.formatted.reply.message', $account_registration['formatted_options']['reply']['message_success'])
      ->set('account_registration.formatted.reply.message_failure', $account_registration['formatted_options']['reply']['message_failure'])
      ->set('account_registration.formatted.activation_email', $account_registration['formatted_options']['activation_email'])
      // Active Hours.
      ->set('active_hours.status', (boolean)$form_state->getValue(['active_hours', 'status']))
      // Days make sense for this form, however storage uses generic 'range' term.
      // Remove keys so it is a raw sequence.
      ->set('active_hours.ranges', array_values($form_state->getValue(['active_hours', 'days'])))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sms_user.settings'];
  }

  /**
   * Build a token element.
   *
   * @return array
   *   A render array.
   */
  protected function buildTokenElement() {
    $tokens = ['sms-message', 'user'];

    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = \Drupal::service('module_handler');
    if ($module_handler->moduleExists('token')) {
      return [
        '#theme' => 'token_tree_link',
        '#token_types' => $tokens,
      ];
    }
    else {
      foreach ($tokens as &$token) {
        $token = "[$token:*]";
      }
      return [
        '#markup' => $this->t('Available tokens include: @token_types', ['@token_types' => implode(' ', $tokens)]),
      ];
    }
  }

}
