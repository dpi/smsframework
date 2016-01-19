<?php
/**
 * @file
 * Contains \Drupal\sms_devel\Plugin\SmsGateway\DevelGateway
 */

namespace Drupal\sms_devel\Plugin\SmsGateway;

use Drupal\Component\Utility\Random;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sms\Plugin\SmsGatewayPluginInterface;

/**
 * Defines a gateway storing transmitted SMS in database for debugging.
 *
 * @SmsGateway(
 *   id = "devel",
 *   label = @Translation("Debug Gateway"),
 * )
 */
class DevelGateway extends SmsGatewayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'autoreply_enabled' => FALSE,
      'autoreply_format' => $this->t('echo: {message}'),
      'receipts_enabled' => FALSE,
      // Log.
      'logfield_refresh' => TRUE,
      'logfield_showsent' => TRUE,
      'logfield_showreceived' => TRUE,
      'logfield_showreceipts' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['sms_devel_virtualgw_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['sms_devel_virtualgw_settings']['sms_devel_virtualgw_autoreply_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable autoreply'),
      '#default_value' => $config['autoreply_enabled'],
    );
    $form['sms_devel_virtualgw_settings']['sms_devel_virtualgw_autoreply_format'] = array(
      '#type' => 'textarea',
      '#rows' => 4,
      '#cols' => 40,
      '#resizable' => FALSE,
      '#default_value' => $config['autoreply_format'],
      '#description' => t('If enabled then the gateway will reply to your messages through sms_incoming()<br />You may use these tokens: {number} {gw_number} {message} {reference}'),
    );
    $form['sms_devel_virtualgw_settings']['sms_devel_virtualgw_receipts_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable receipts'),
      '#default_value' => $config['receipts_enabled'],
      '#description' => t('If enabled then the gateway will provide a message receipt through sms_receipt()'),
    );
    $form['sms_devel_virtualgw_settings']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#submit' => array('sms_devel_virtualgw_form_save_settings'),
    );

    $form['sms_devel_virtualgw_send'] = array(
      '#type' => 'fieldset',
      '#title' => t('Send a message from the gateway to SMS Framework'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['sms_devel_virtualgw_send']['sms_devel_virtualgw_from'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('From ($number)'),
      '#size' => 30,
    );
    $form['sms_devel_virtualgw_send']['sms_devel_virtualgw_to'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('To ($options[\'gw_number\'])'),
      '#size' => 30,
    );
    $form['sms_devel_virtualgw_send']['sms_devel_virtualgw_message'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Message ($message)'),
      '#rows' => 4,
      '#cols' => 40,
      '#resizable' => FALSE,
    );
    $form['sms_devel_virtualgw_send']['send'] = array(
      '#type' => 'button',
      '#value' => t('Send'),
      '#ajax' => array(
        'callback' => 'sms_devel_virtualgw_ahah_send',
      ),
    );

    $form['sms_devel_virtualgw_log'] = array(
      '#type' => 'fieldset',
      '#title' => t('Activity log'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['sms_devel_virtualgw_log']['sms_devel_virtualgw_logfield_refresh'] = array(
      '#type' => 'checkbox',
      '#title' => t('Refresh the logfield'),
      '#default_value' => $config['logfield_refresh'],
    );
    $form['sms_devel_virtualgw_log']['sms_devel_virtualgw_logfield_showsent'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show sent messages'),
      '#default_value' => $config['logfield_showsent'],
    );
    $form['sms_devel_virtualgw_log']['sms_devel_virtualgw_logfield_showreceived'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show received messages'),
      '#default_value' => $config['logfield_showreceived'],
    );
    $form['sms_devel_virtualgw_log']['sms_devel_virtualgw_logfield_showreceipts'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show message receipts'),
      '#default_value' => $config['logfield_showreceipts'],
    );
    $form['sms_devel_virtualgw_log']['sms_devel_virtualgw_logfield_lines'] = array(
      '#type' => 'textfield',
      '#title' => t('Number of recent activity records to pull'),
      '#size' => 4,
    );
    $form['sms_devel_virtualgw_log']['sms_devel_virtualgw_logfield_refreshes'] = array(
      '#type' => 'item',
      '#title' => t('Refresh count'),
      '#value' => t(''),
    );
    $form['sms_devel_virtualgw_log']['sms_devel_virtualgw_logfield'] = array(
      '#type' => 'textarea',
      '#title' => t('Virtual gateway activity'),
      '#rows' => 20,
      '#cols' => 60,
      '#resizable' => TRUE,
      '#prefix' => '<div id="logfield">',
      '#value' => 'Sorry, this feature is not finished yet.',
      '#suffix' => '</div>',
    );
    $form['sms_devel_virtualgw_log']['get'] = array(
      '#type' => 'button',
      '#value' => t('Get activity'),
      '#ajax' => array(
        'callback' => 'sms_devel_virtualgw_js_getactivity',
        'method' => 'replace',
        'wrapper' => 'logfield',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // @todo save vars.
    //    $this->configuration['var'] = value

    //sms_devel_virtualgw_form_save_settings()
    //variable_set('sms_devel_virtualgw_autoreply_enabled',
    //  $form_state['values']['sms_devel_virtualgw_autoreply_enabled']);
    //variable_set('sms_devel_virtualgw_autoreply_format',
    //  $form_state['values']['sms_devel_virtualgw_autoreply_format']);
    //variable_set('sms_devel_virtualgw_receipts_enabled',
    //  $form_state['values']['sms_devel_virtualgw_receipts_enabled']);
  }

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms_message, array $options) {
    // Set a default sender if it is not specified.
    if (isset($options['gw_number'])) {
      $options['gw_number'] = '99999';
    }

    // Set a default reference if it is not specified.
    if (isset($options['reference'])) {
      $random = new Random;
      $options['reference'] = $random->name(32);
    }

    // Write log record for outgoing message.
    sms_devel_virtualgw_log_insert(SMS_DEVEL_VIRTUALGW_TYPE_OUT, $sms_message, $options);

    // Invoke additional virtual gateway features eg: autoreplies, receipts.
    sms_devel_virtualgw_sendlogic($sms_message, $options);

    // Always return success.
    return new SmsMessageResult([
      'status' => TRUE,
      'status_code' => SmsGatewayPluginInterface::STATUS_OK,
      'gateway_status_code' => 'OK',
      'gateway_status_text' => 'sms_devel_virtualgw: send: OK',
    ]);
  }

}
