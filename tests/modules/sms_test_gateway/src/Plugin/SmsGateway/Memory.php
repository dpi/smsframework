<?php

/**
 * @file
 * Contains \Drupal\sms_test_gateway\Plugin\SmsGateway\Memory
 */

namespace Drupal\sms_test_gateway\Plugin\SmsGateway;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Random;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsDeliveryReportInterface;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines a gateway storing transmitted SMS in memory.
 *
 * @SmsGateway(
 *   id = "memory",
 *   label = @Translation("Memory"),
 * )
 */
class Memory extends SmsGatewayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'widget' => '',
      // Store the ID of gateway config. See static::send().
      'gateway_id' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['widget'] = array(
      '#type' => 'textfield',
      '#title' => t('Widget'),
      '#description' => t('Enter a widget.'),
      '#default_value' => $config['widget'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['widget'] = $form_state->getValue('widget');
  }

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms_message, array $options) {
    $state = \Drupal::state()->get('sms_test_gateway.memory.send', []);

    $gateway_id = $this->configuration['gateway_id'];
    $state[$gateway_id][] = $sms_message;
    \Drupal::state()->set('sms_test_gateway.memory.send', $state);

    $reports = \Drupal::state()->get('sms_test_gateway.memory.report', []);
    $latest_reports = $this->randomDeliveryReports($sms_message);
    // Update the delivery reports.
    $reports = $latest_reports + $reports;
    \Drupal::state()->set('sms_test_gateway.memory.report', $reports);
    return new SmsMessageResult([
      'status' => TRUE,
      'reports' => $latest_reports,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function parseDeliveryReports(Request $request, Response $response) {
    $data = Json::decode($request->request->get('delivery_report'));
    $latest_reports = [];
    foreach ($data['reports'] as $report) {
      $latest_reports[$report['message_id']] = new SmsDeliveryReport([
        'recipient' => $report['recipient'],
        'message_id' => $report['message_id'],
        'time_sent' => $report['time_sent'],
        'time_delivered' => $report['time_delivered'],
        'status' => $report['status'],
        'gateway_status' => $report['gateway_status'],
        'gateway_status_code' => $report['gateway_status_code'],
        'gateway_status_description' => $report['gateway_status_description'],
      ]);
    }

    // Update the latest delivery reports in \Drupal::state().
    $reports = \Drupal::state()->get('sms_test_gateway.memory.report', []);
    $reports = $latest_reports + $reports;
    \Drupal::state()->set('sms_test_gateway.memory.report', $reports);

    // Set the response.
    $response->setContent('custom response content');
    return $latest_reports;
  }

  /**
   * Generates random delivery reports for each of the recipients of a message.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms_message
   *   The SMS message
   *
   * @return \Drupal\sms\Message\SmsDeliveryReportInterface[]
   *   An array of delivery reports.
   */
  protected function randomDeliveryReports(SmsMessageInterface $sms_message) {
    $random = new Random();
    $reports = [];
    foreach ($sms_message->getRecipients() as $number) {
      $message_id = $random->name(16);
      $reports[$message_id] = new SmsDeliveryReport([
        'recipient' => $number,
        'message_id' => $message_id,
        'time_sent' => time(),
        'time_delivered' => time() + rand(0, 10),
        'status' => SmsDeliveryReportInterface::STATUS_SENT,
        'gateway_status' => 'SENT',
        'gateway_status_code' => '200',
        'gateway_status_description' => 'Sent to memory gateway',
      ]);
    }
    return $reports;
  }

  public function validateNumber($numbers) {
    $errors = array();
    foreach ($numbers as $number) {
      $code = substr($number, 0, 3);
      if (preg_match('/[^0-9]/', $number)) {
        $errors[] = t('Non-numeric character found in number.');
      }
      if (strlen($number) > 15 || strlen($number) < 10) {
        $errors[] = t('Number longer than 15 digits or shorter than 10 digits.');
      }
      if ($code == '990' || $code == '997' || $code == '999') {
        $errors[] = t('Country code not allowed');
      }
    }
    return $errors;
  }

}
