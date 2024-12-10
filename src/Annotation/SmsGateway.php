<?php

declare(strict_types=1);

// phpcs:disable Drupal.NamingConventions.ValidVariableName.LowerCamelName

namespace Drupal\sms\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines SmsGateway Annotation object.
 *
 * @Annotation
 * @phpstan-type SmsGatewayDefinition array{id: string, label: \Drupal\Core\StringTranslation\TranslatableMarkup, outgoing_message_max_recipients?: int<-1, max>, incoming?: bool, incoming_route?: bool, schedule_aware?: bool, reports_pull?: bool, reports_push?: bool, credit_balance_available?: bool}
 */
class SmsGateway extends Plugin {

  /**
   * The machine name of the sms gateway.
   *
   * @var string
   */
  protected $id;

  /**
   * Translated user-readable label.
   *
   * @var string
   */
  protected $label;

  /**
   * Maximum number of recipients per outgoing message.
   *
   * Use -1 for no limit.
   *
   * @var int
   */
  protected $outgoing_message_max_recipients;

  /**
   * Whether the gateway supports receiving messages.
   *
   * @var bool
   */
  protected $incoming;

  /**
   * Whether to automatically create a route for receiving incoming messages.
   *
   * @var bool
   */
  protected $incoming_route;

  /**
   * Whether the gateway is capable of delaying messages until a date.
   *
   * Schedule aware gateways must extract sending time from all message
   * getSendTime() method. Keep in mind this method is only available if the
   * message is a SMS message entity. See the schedule aware gateway
   * implementation in the test modules for an example.
   *
   * @var bool
   */
  protected $schedule_aware;

  /**
   * Whether the gateway can pull reports.
   *
   * @var bool
   * @see \Drupal\sms\Entity\SmsGatewayInterface::supportsReportsPull()
   */
  protected $reports_pull;

  /**
   * Whether the gateway can handle reports pushed to the site.
   *
   * @var bool
   * @see \Drupal\sms\Entity\SmsGatewayInterface::supportsReportsPush()
   */
  protected $reports_push;

  /**
   * Whether the gateway supports queries of current credit balance.
   *
   * @var bool
   * @see \Drupal\sms\Entity\SmsGatewayInterface::supportsCreditBalanceQuery()
   */
  protected $credit_balance_available;

}
