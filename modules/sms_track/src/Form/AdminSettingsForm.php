<?php
/**
 * @file
 * Contains \Drupal\sms_track\Form\AdminSettingsForm
 */

namespace Drupal\sms_track\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class AdminSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_track_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get sms_track configuration.
    $config = $this->config('sms_track.settings');
    // Archive section.
    $form['archive'] = array(
      '#type'  => 'fieldset',
      '#title' => 'Message archiving',
      '#collapsible' => TRUE,
      '#collapsed'   => FALSE,
    );
    $form['archive']['archive_dir'] = array(
      '#type'  => 'select',
      '#title' => 'Archive mode',
      '#default_value' => $config->get('archive_dir'),
      '#options' => array(
        SMS_DIR_NONE => 'No archiving [default]',
        SMS_DIR_OUT  => 'Record outgoing messages only',
        SMS_DIR_IN   => 'Record incoming messages only',
        SMS_DIR_ALL  => 'Record both outgoing and incoming messages',
      ),
      '#description' => t('Note that this will revert to the default option when the SMS Tracking module is disabled.'),
    );
    $form['archive']['archive_max_age_days'] = array(
      '#type'  => 'textfield',
      '#title' => 'Purge messages after n days',
      '#size'          => 3,
      '#maxlength'     => 3,
      '#default_value' => $config->get('archive_max_age_days'),
      '#description'   => 'Set to 0 (zero) to disable archive purge. This will only work if you have cron configured correctly.',
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get sms_track configuration
    $config = $this->configFactory()->getEditable('sms_track.settings');
    $archive_dir_old = $config->get('archive_dir');
    $archive_dir = $form_state->getValue('archive_dir');
    $config->set('archive_dir', $archive_dir);

    $archive_max_age_days = $form_state->getValue('archive_max_age_days');
    $config->set('archive_max_age_days', $archive_max_age_days);

    $config->save();

    // Trigger watchdog messages
    if ($archive_dir_old && ! $archive_dir) {
      $this->logger('sms_track')->notice('SMS Tracking archive collector DISABLED');
    }
    if (! $archive_dir_old && $archive_dir) {
      $this->logger('sms_track')->notice('SMS Tracking archive collector enabled');
    }

    drupal_set_message($this->t('Settings saved.'));
  }

  /**
   * Provides the admin view page from the sms_track view in database.
   *
   * @return string
   *   HTML content string.
   */
  function adminView() {
    return views_embed_view('sms_track');
  }

}
