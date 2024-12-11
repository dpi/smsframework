<?php

declare(strict_types=1);

namespace Drupal\sms_test_gateway\Plugin\SmsGateway;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sms\Attribute\SmsGateway;

/**
 * Defines a gateway requiring chunked messages.
 */
#[SmsGateway(
  id: self::PLUGIN_ID,
  label: new TranslatableMarkup('Memory Chunked'),
  incoming: TRUE,
  outgoingMessageMaxRecipients: 2,
)]
final class MemoryChunked extends Memory {

  public const PLUGIN_ID = 'memory_chunked';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [];
  }

}
