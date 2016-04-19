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
    $config = $this->config('sms_user.settings');

    // Active hours.
    $form['active_hours'] = [
      '#type' => 'details',
      '#title' => $this->t('Active hours'),
      '#description' => $this->t('Active hours will suspend transmission of automated SMS messages until the users local time is between any of these hours. The site default timezone is used if a user has not selected a timezone. Active hours are not applied to SMS messages created as a result of direct user action. Messages which are already queued are not retroactively updated.'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['active_hours']['status'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable active hours'),
      '#default_value' => $config->get('active_hours.status'),
    );

    $form['active_hours']['days'] = [
      '#type' => 'table',
      '#header' => [
        'day' => $this->t('Day'),
        'start' => $this->t('Start time'),
        'end' => $this->t('End time'),
      ],
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
      ];

      $timestamp = strtotime('+1 day', $timestamp);
      $form['active_hours']['days'][$day_lower] = $row;
    }

    // Registration settings.
    $form['registration'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Registration settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['registration']['registration_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable registration'),
      '#default_value' => $config->get('registration_enabled'),
      '#description' => $this->t('If selected, users can create user accounts via SMS.'),
    );
    $form['registration']['allow_password'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow password creation'),
      '#default_value' => $config->get('allow_password'),
      '#description' => $this->t('If selected, the user will be allowed to include a password in their registration request -- the password will be the first word in the first line of the request.'),
    );
    $form['registration']['new_account_message'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('New user message'),
      '#default_value' => $config->get('new_account_message'),
      '#description' => $this->t('The message that will be sent to newly registered users.  Leave empty for no message.'),
    );
  
    // Add the token help to a collapsed fieldset at the end of the registration page.
    $form['registration']['tokens']['token_help'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Available Tokens List'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['registration']['tokens']['token_help']['content'] = array(
      '#theme' => 'token_tree',
      '#token_types' => array('sms_user'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
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
      foreach (Element::children($form['active_hours']['days']) as $day) {
        $form_state->setError($form['active_hours']['days'][$day]['start'], $this->t('If active hours hours are enabled there must be at least one enabled day.'));
      }
    }

    // Ensure end times are greater than start times.
    foreach ($form_state->getValue(['active_hours', 'days']) as $day => $row) {
      $start = new DrupalDateTime($row['start']);
      $end = new DrupalDateTime($row['end']);
      if ($end < $start) {
        $form_state->setError($form['active_hours']['days'][$day]['end'], $this->t('End time must be greater than start time.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Clean up the form_state values and save to config
    $form_state->cleanValues();
    $settings = $form_state->getValues();
    $settings['active_hours']['status'] = (boolean)$settings['active_hours']['status'];

    // Days make sense for this form, however storage uses generic 'range' term.
    // Remove keys so it is a raw sequence.
    $settings['active_hours']['ranges'] = array_values($settings['active_hours']['days']);

    unset($settings['active_hours']['days']);

    $this->config('sms_user.settings')
      ->setData($settings)
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sms_user.settings'];
  }

}
