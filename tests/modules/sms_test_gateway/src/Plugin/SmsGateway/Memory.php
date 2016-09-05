<?php

/**
 * @file
 * Contains \Drupal\sms_test_gateway\Plugin\SmsGateway\Memory
 */

namespace Drupal\sms_test_gateway\Plugin\SmsGateway;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Random;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms\Plugin\SmsGatewayPluginIncomingInterface;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\sms\Message\SmsMessageReportStatus;

/**
 * Defines a gateway storing transmitted SMS in memory.
 *
 * @SmsGateway(
 *   id = "memory",
 *   label = @Translation("Memory"),
 *   outgoing_message_max_recipients = -1,
 *   schedule_aware = FALSE,
 * )
 */
class Memory extends SmsGatewayPluginBase implements SmsGatewayPluginIncomingInterface{

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
  public function send(SmsMessageInterface $sms_message) {
    $state = \Drupal::state()->get('sms_test_gateway.memory.send', []);

    $gateway_id = $this->configuration['gateway_id'];
    $state[$gateway_id][] = $sms_message;
    \Drupal::state()->set('sms_test_gateway.memory.send', $state);

    $reports = \Drupal::state()->get('sms_test_gateway.memory.report', []);
    $latest_reports = $this->randomDeliveryReports($sms_message);
    // Update the delivery reports.
    $reports[$gateway_id] = $latest_reports + $reports;
    \Drupal::state()->set('sms_test_gateway.memory.report', $reports);

    return (new SmsMessageResult())
      ->setReports($latest_reports);
  }

  /**
   * {@inheritdoc}
   */
  public function incoming(SmsMessageInterface $sms_message) {
    // @todo Contents of this method are subject to proposals made in
    // https://www.drupal.org/node/2712579

    // Set state so we test this method is executed, remove this after above is
    // addressed.
    \Drupal::state()->set('sms_test_gateway.memory.incoming', TRUE);

    return new SmsMessageResult([
      'status' => TRUE,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function parseDeliveryReports(Request $request, Response $response) {
    $data = Json::decode($request->request->get('delivery_report'));
    $latest_reports = [];
    foreach ($data['reports'] as $report) {
      $message_id = $report['message_id'];
      $latest_reports[] = (new SmsDeliveryReport())
        ->setRecipients([$report['recipient']])
        ->setMessageId($message_id)
        ->setStatus(SmsMessageReportStatus::DELIVERED)
        ->setStatusMessage($report['status'])
        ->setTimeQueued($report['time_sent'])
        ->setTimeDelivered($report['time_delivered']);
    }

    // Update the latest delivery reports in \Drupal::state().
    $gateway_id = $this->configuration['gateway_id'];
    $reports = \Drupal::state()->get('sms_test_gateway.memory.report', []);
    $reports[$gateway_id] = $latest_reports + $reports;
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
      $reports[] = (new SmsDeliveryReport())
        ->setRecipients([$number])
        ->setMessageId($random->name(16))
        ->setStatus(SmsMessageReportStatus::QUEUED)
        ->setStatusMessage('Sent to memory gateway')
        ->setTimeQueued(time())
        ->setTimeDelivered(time() + rand(0, 10));
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
