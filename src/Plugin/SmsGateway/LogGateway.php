<?php

declare(strict_types=1);

namespace Drupal\sms\Plugin\SmsGateway;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sms\Attribute\SmsGateway;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageResultInterface;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a logger gateway for testing and debugging.
 *
 * @SmsGateway(
 *   id = \Drupal\sms\Plugin\SmsGateway\LogGateway::PLUGIN_ID,
 *   label = @Translation("Drupal log"),
 *   outgoing_message_max_recipients = -1,
 * )
 */
#[SmsGateway(
  id: self::PLUGIN_ID,
  label: new TranslatableMarkup('Drupal log'),
  outgoingMessageMaxRecipients: -1,
)]
final class LogGateway extends SmsGatewayPluginBase implements ContainerFactoryPluginInterface {

  public const PLUGIN_ID = 'log';

  /**
   * A logger instance.
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a LogGateway object.
   */
  final public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LoggerChannelFactoryInterface $logger_factory,
    protected TimeInterface $time,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $definition = $this->getPluginDefinition();
    $this->logger = $logger_factory->get($definition['provider'] . '.' . $definition['id']);
  }

  /**
   * {@inheritdoc}
   */
  final public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('datetime.time'),
    );
  }

  public function send(SmsMessageInterface $sms): SmsMessageResultInterface {
    $this->logger->notice('SMS message sent to %number with the text: @message', [
      '%number' => \implode(', ', $sms->getRecipients()),
      '@message' => $sms->getMessage(),
    ]);

    $result = new SmsMessageResult();
    foreach ($sms->getRecipients() as $number) {
      $report = (new SmsDeliveryReport())
        ->setRecipient($number)
        ->setStatus(SmsMessageReportStatus::DELIVERED)
        ->setStatusMessage('DELIVERED')
        ->setTimeDelivered($this->time->getRequestTime());
      $result->addReport($report);
    }

    return $result;
  }

}
