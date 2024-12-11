<?php

declare(strict_types=1);

namespace Drupal\sms\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Attribute for defining a SMS Gateway.
 *
 * @see plugin_api
 * @phpstan-type SmsGatewayDefinition array{id: string, label: \Drupal\Core\StringTranslation\TranslatableMarkup, outgoingMessageMaxRecipients: int<-1, max>, incoming: bool, incomingRoute: bool, scheduleAware: bool, reportsPull: bool, reportsPush: bool, creditBalanceAvailable: bool}
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class SmsGateway extends Plugin {

  private const USE_NAMED_PARAMETERS = 'Using this attribute without named parameters is not supported.';

  /**
   * @phpstan-param class-string<\Drupal\Component\Plugin\Derivative\DeriverInterface>|null $deriver
   * @phpstan-param int<-1, max> $outgoingMessageMaxRecipients
   */
  public function __construct(
    string $id,
    public readonly TranslatableMarkup $label,
    ?string $useNamedParameters = self::USE_NAMED_PARAMETERS,
    ?string $deriver = NULL,
    public int $outgoingMessageMaxRecipients = 1,
    public bool $incoming = FALSE,
    public bool $incomingRoute = FALSE,
    public bool $scheduleAware = FALSE,
    public bool $reportsPull = FALSE,
    public bool $reportsPush = FALSE,
    public bool $creditBalanceAvailable = FALSE,
  ) {
    parent::__construct($id, $deriver);

    if (self::USE_NAMED_PARAMETERS !== $useNamedParameters) {
      throw new \LogicException(self::USE_NAMED_PARAMETERS);
    }
  }

}
