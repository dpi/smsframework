<?php

declare(strict_types=1);

namespace Drupal\Tests\sms\Traits;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\ErrorHandler\BufferingLogger;

/**
 * Logger trait.
 *
 * Via sm.
 */
trait SmsLoggerTrait {

  /**
   * Gets logs from buffer and cleans out buffer.
   *
   * Reconstructs logs into plain strings.
   *
   * @param array<array{\Drupal\Core\Logger\RfcLogLevel::*, string|\Stringable, mixed[]}>|null $logBuffer
   *   A log buffer from getLogBuffer, or provide an existing value fetched from
   *   getLogBuffer. This is a workaround for the logger clearing values on
   *   call.
   *
   * @return array<array{severity: \Drupal\Core\Logger\RfcLogLevel::*, message: string}>
   *   Logs from buffer, where values are an array with keys: severity, message.
   */
  private function getLogs(?array $logBuffer = NULL): array {
    $logs = \array_map(static function (array $log): array {
      [$severity, $message, $context] = $log;
      return [
        'severity' => $severity,
        'message' => \str_replace(\array_keys($context), \array_values($context), (string) $message),
      ];
    }, $logBuffer ?? $this->getLogBuffer());
    return \array_values($logs);
  }

  /**
   * Gets logs from buffer and cleans out buffer.
   *
   * @return array<array{\Drupal\Core\Logger\RfcLogLevel::*, string|\Stringable, mixed[]}>
   *   Logs from buffer, where values are an array with keys: severity, message.
   */
  private function getLogBuffer(): array {
    return $this->testLogger()->cleanLogs();
  }

  /**
   * Registers logger to container.
   */
  public function loggerRegister(ContainerBuilder $container): void {
    $container
      ->register('.test_logger', BufferingLogger::class)
      ->addTag('logger');
  }

  /**
   * Clears out logs on teardown.
   *
   * Must be called otherwise logs are sent to stderr.
   */
  protected function loggerTeardown(): void {
    $this->testLogger()->cleanLogs();
  }

  private function testLogger(): BufferingLogger {
    /** @var \Symfony\Component\ErrorHandler\BufferingLogger */
    return $this->container->get('.test_logger');
  }

}
