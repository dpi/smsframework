<?php

declare(strict_types=1);

namespace Drupal\sms_test_gateway\Plugin\SmsGateway;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Random;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\sms\Event\SmsMessageEvent;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageResultInterface;
use Drupal\sms\Plugin\SmsGateway\SmsIncomingEventProcessorInterface;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms_test_gateway\EventSubscriber\SmsTestGatewayEventSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines a gateway storing transmitted SMS in memory.
 *
 * @SmsGateway(
 *   id = "memory",
 *   label = @Translation("Memory"),
 *   outgoing_message_max_recipients = -1,
 *   incoming = TRUE,
 *   schedule_aware = FALSE,
 *   reports_pull = TRUE,
 *   reports_push = TRUE,
 *   credit_balance_available = TRUE,
 * )
 */
class Memory extends SmsGatewayPluginBase implements SmsIncomingEventProcessorInterface {

  public const STATE_MESSAGES = 'sms_test_gateway.memory.send';
  public const STATE_REPORTS = 'sms_test_gateway.memory.report';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'widget' => '',
      // Store the ID of gateway config. See static::send().
      'gateway_id' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['widget'] = [
      '#type' => 'textfield',
      '#title' => \t('Widget'),
      '#description' => \t('Enter a widget.'),
      '#default_value' => $config['widget'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['widget'] = $form_state->getValue('widget');
  }

  public function send(SmsMessageInterface $sms): SmsMessageResultInterface {
    $gateway_id = $this->configuration['gateway_id'];

    // Message.
    /** @var array<string, array<\Drupal\sms\Message\SmsMessageInterface>> $state */
    $state = static::state()->get(static::STATE_MESSAGES, []);
    $state[$gateway_id][] = $sms;
    static::state()->set(static::STATE_MESSAGES, $state);

    // Reports.
    /** @var array<string, array<string, \Drupal\sms\Message\SmsDeliveryReportInterface>> $reports */
    $reports = static::state()->get(Memory::STATE_REPORTS, []);
    $gateway_reports = $reports[$gateway_id] ?? [];
    $new_reports = $this->randomDeliveryReports($sms);
    $reports[$gateway_id] = \array_merge($gateway_reports, $new_reports);
    static::state()->set(Memory::STATE_REPORTS, $reports);

    return (new SmsMessageResult())
      ->setReports($new_reports);
  }

  public function incomingEvent(SmsMessageEvent $event): void {
    // @todo Contents of this method are subject to proposals made in
    // https://www.drupal.org/node/2712579
    // Set state so we test this method is executed, remove this after above is
    // addressed.
    static::state()->set(SmsTestGatewayEventSubscriber::STATE_MEMORY_INCOMING, TRUE);

    /** @var array $execution_order */
    $execution_order = static::state()->get('sms_test_event_subscriber__execution_order', []);
    $execution_order[] = __METHOD__;
    static::state()->set('sms_test_event_subscriber__execution_order', $execution_order);
  }

  /**
   * {@inheritdoc}
   */
  public function parseDeliveryReports(Request $request, Response $response): array {
    $gateway_id = $this->configuration['gateway_id'];
    /** @var array<string, array<string, \Drupal\sms\Message\SmsDeliveryReportInterface>> $memory_reports */
    $memory_reports = static::state()->get(Memory::STATE_REPORTS, []);

    $data = Json::decode($request->request->get('delivery_report'));
    $return = [];
    foreach ($data['reports'] as $report) {
      $message_id = $report['message_id'];
      $new_report = (new SmsDeliveryReport())
        ->setRecipient($report['recipient'])
        ->setMessageId($message_id)
        ->setStatus($report['status'])
        ->setStatusMessage($report['status_message'])
        ->setStatusTime($report['status_time']);
      // Backfill the specific values.
      if ($report['status'] === SmsMessageReportStatus::QUEUED) {
        $new_report->setTimeQueued($report['status_time']);
      }
      if ($report['status'] === SmsMessageReportStatus::DELIVERED) {
        $new_report->setTimeDelivered($report['status_time']);
      }

      // Set separately since this method should not have meaningful keys.
      $return[] = $new_report;
      // Reports in state must be keyed by message ID.
      $memory_reports[$gateway_id][$message_id] = $new_report;
    }

    static::state()->set(Memory::STATE_REPORTS, $memory_reports);

    // Set the response.
    $response->setContent('custom response content');

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeliveryReports(?array $message_ids = NULL): array {
    return [];
  }

  /**
   * Generates random delivery reports for each of the recipients of a message.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms_message
   *   The SMS message.
   *
   * @return \Drupal\sms\Message\SmsDeliveryReportInterface[]
   *   An array of delivery reports.
   */
  protected function randomDeliveryReports(SmsMessageInterface $sms_message): array {
    $random = new Random();
    $request_time = static::time()->getRequestTime();
    $reports = [];
    foreach ($sms_message->getRecipients() as $number) {
      $reports[] = (new SmsDeliveryReport())
        ->setRecipient($number)
        ->setMessageId($random->name(16))
        ->setStatus(SmsMessageReportStatus::QUEUED)
        ->setStatusTime($request_time)
        ->setStatusMessage('Sent to memory gateway')
        ->setTimeQueued($request_time)
        ->setTimeDelivered($request_time + \rand(0, 10));
    }
    return $reports;
  }

  public function getCreditsBalance(): ?float {
    return 13.36;
  }

  protected static function state(): StateInterface {
    return \Drupal::state();
  }

  protected static function time(): TimeInterface {
    return \Drupal::time();
  }

}
