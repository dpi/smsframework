<?php

declare(strict_types=1);

namespace Drupal\Tests\sms\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\sms\Traits\SmsLoggerTrait;

/**
 * Base class for SMS Framework unit tests.
 *
 * @phpstan-import-type SmsVerificationParameter from \Drupal\sms\SmsCompilerPass
 */
abstract class SmsFrameworkKernelBase extends KernelTestBase {

  use SmsLoggerTrait;

  protected const FIELD_NAME = 'phone_number';

  /**
   * @var null|SmsVerificationParameter
   */
  private ?array $smsVerificationsParameter = NULL;

  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('sms');
  }

  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container->setParameter('notifier.channel_policy', [
      'urgent' => ['sms'],
      'high' => ['sms'],
      'medium' => ['sms'],
      'low' => ['sms'],
    ]);
    $container->setParameter('notifier.field_mapping.sms', [
      [
        'entity_type' => 'entity_test',
        'bundle' => 'entity_test',
        'field_name' => static::FIELD_NAME,
        'verifications' => TRUE,
      ],
    ]);
    $container->setParameter('sms.transports', [
      'fakesms+logger' => 'fakesms+logger://default',
    ]);

    if (NULL !== $this->smsVerificationsParameter) {
      $container->setParameter('sms.verification', $this->smsVerificationsParameter);
    }

    $this->loggerRegister($container);
  }

  protected function tearDown(): void {
    $this->loggerTeardown();
    parent::tearDown();
  }

  protected function createPhoneNumberField(string $entityTypeId, string $bundle, ?string $fieldName): FieldConfigInterface {
    $fieldName ??= \mb_strtolower($this->randomMachineName());
    $storage = FieldStorageConfig::create([
      'entity_type' => $entityTypeId,
      'field_name' => $fieldName,
      'type' => 'telephone',
    ]);
    $storage->save();

    $config = FieldConfig::create([
      'entity_type' => $entityTypeId,
      'bundle' => $bundle,
      'field_name' => $storage->getName(),
    ]);
    $config->save();

    return $config;
  }

  /**
   * Sets the parameter value and triggers a rebuild.
   *
   * @phpstan-param SmsVerificationParameter $value
   */
  protected function setVerificationsParameter(array $value): void {
    $this->smsVerificationsParameter = $value;
    \Drupal::service('kernel')->rebuildContainer();
  }

}
