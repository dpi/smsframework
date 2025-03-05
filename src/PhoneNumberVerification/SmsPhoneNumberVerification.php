<?php

declare(strict_types=1);

namespace Drupal\sms\PhoneNumberVerification;

use Drupal\another_entity_iterator\EntityIterator\EntityIteratorFactoryInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Utility\Token;
use Drupal\sms\Entity\PhoneNumberVerification;
use Drupal\sms\Entity\PhoneNumberVerificationInterface;
use Drupal\sms\Hook\SmsTokenHooks;
use Drupal\sms\LogReference;
use Drupal\sms\PhoneNumber\PhoneNumberInterface;
use Drupal\sms\PhoneNumber\RecipientProxy;
use Drupal\sms\PhoneNumberVerification\CodeGenerator\CodeGeneratorInterface;
use Drupal\sms\PhoneNumberVerification\CodeGenerator\VerificationCodeContext;
use Drupal\sms\PhoneNumberVerification\CodeGenerator\VerificationCodeInterface;
use Drupal\sms\PhoneNumberVerification\Enum\Verified;
use Drupal\sms\PhoneNumberVerification\Exception\NoPhoneNumberVerificationByCodeExists;
use Drupal\sms\PhoneNumberVerification\Exception\NoPhoneNumberVerificationExists;
use Drupal\sms\PhoneNumberVerification\Object\ObjectWithPhoneNumberAdapter;
use Drupal\sms\PhoneNumberVerification\Object\ObjectWithPhoneNumberInterface;
use Drupal\sms\Plugin\QueueWorker\DeletePhoneVerification;
use Psr\Log\LoggerInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;

final class SmsPhoneNumberVerification implements SmsPhoneNumberVerificationInterface {

  /**
   * @phpstan-param positive-int $unverifiedLifetime
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly CodeGeneratorInterface $codeGenerator,
    private readonly NotifierInterface $notifier,
    private readonly Token $token,
    private readonly TimeInterface $time,
    private readonly ?LoggerInterface $logger,
    private readonly EntityIteratorFactoryInterface $iteratorFactory,
    private readonly QueueFactory $queueFactory,
    private readonly string $verificationMessage,
    private readonly int $unverifiedLifetime,
    private readonly bool $enforceUniquePhoneNumbers,
  ) {}

  public function isVerified(ObjectWithPhoneNumberInterface|EntityInterface $for, PhoneNumberInterface $phoneNumber): Verified {
    $verification = $this->getPhoneNumberVerification($for, $phoneNumber);
    return $verification?->getStatus() ?? Verified::Unverified;
  }

  /**
   * @phpstan-ignore-next-line
   */
  private function getPhoneNumberVerificationByCode(VerificationCodeInterface $verificationCode): ?PhoneNumberVerificationInterface {
    /** @var \Drupal\sms\Entity\PhoneNumberVerificationInterface[] $verifications */
    $verifications = $this->phoneNumberVerificationStorage()
      ->loadByProperties([
        'code' => $verificationCode->getCode(),
      ]);
    return $verifications !== [] ? $verifications[\array_key_first($verifications)] : NULL;
  }

  public function getPhoneNumberVerification(ObjectWithPhoneNumberInterface|EntityInterface $for, PhoneNumberInterface $phoneNumber): ?PhoneNumberVerificationInterface {
    $for = ObjectWithPhoneNumberAdapter::from($for);

    /** @var \Drupal\sms\Entity\PhoneNumberVerificationInterface[] $verifications */
    $verifications = $this->phoneNumberVerificationStorage()
      ->loadByProperties([
        'entity__target_type' => $for->getVerifiedType(),
        'entity__target_id' => $for->getVerifiedIdentifier(),
        'phone' => $phoneNumber->getPhoneNumber(),
      ]);
    return $verifications !== [] ? $verifications[\array_key_first($verifications)] : NULL;
  }

  public function newPhoneVerification(ObjectWithPhoneNumberInterface|EntityInterface $for, PhoneNumberInterface $phoneNumber): void {
    $recipient = RecipientProxy::createFromPhoneNumber($phoneNumber);
    $notification = new Notification($this->verificationMessage);

    $code = $this->codeGenerator->generateCode(new VerificationCodeContext($for, $phoneNumber));

    $data = [];
    $data[SmsTokenHooks::TOKEN_SMS_MESSAGE] = $notification;
    $data[SmsTokenHooks::TOKEN_SMS_RECIPIENT] = $recipient;
    $data[SmsTokenHooks::TOKEN_SMS_VERIFICATION_CODE] = $code;
    $notification->subject($this->token->replace($notification->getSubject(), $data));

    /** @var \Drupal\sms\Entity\PhoneNumberVerificationInterface $verification */
    $verification = $this->phoneNumberVerificationStorage()->create();
    $verification
      ->setVerificationCode($code)
      ->setStatus(Verified::Unverified)
      ->setPhoneNumber($phoneNumber)
      ->setFor($for)
      ->save();

    $this->notifier->send(
      $notification,
      $recipient,
    );

    $entity = $verification->getFor();
    $this->logger?->info('Created verification for @entity @phone_number (#@id)', [
      '@entity' => (string) LogReference::create($entity),
      '@id' => $verification->id(),
      '@phone_number' => (string) $verification->getPhoneNumber(),
    ]);
  }

  public function claimableVerificationByCodeExists(VerificationCodeInterface $verificationCode): bool {
    $min = $this->now()->modify(\sprintf('-%s seconds', $this->unverifiedLifetime));

    $ids = $this->phoneNumberVerificationStorage()
      ->getQuery()
      ->condition('code', $verificationCode->getCode())
      ->condition('status', Verified::Unverified->databaseValue())
      // Ignore expired.
      ->condition('created', $min->getTimestamp(), '>')
      ->accessCheck(FALSE)
      ->execute();
    $verifications = $this->phoneNumberVerificationStorage()->loadMultiple($ids);

    return $verifications !== [];
  }

  public function verify(ObjectWithPhoneNumberInterface|EntityInterface $for, PhoneNumberInterface $phoneNumber): void {
    $verification = $this->getPhoneNumberVerification($for, $phoneNumber) ?? throw new NoPhoneNumberVerificationExists($for, $phoneNumber);
    $this->internalVerify($verification);
  }

  public function verifyByCode(VerificationCodeInterface $verificationCode): void {
    /** @var \Drupal\sms\Entity\PhoneNumberVerificationInterface[] $verifications */
    $verifications = $this->phoneNumberVerificationStorage()
      ->loadByProperties([
        'code' => $verificationCode->getCode(),
        'status' => Verified::Unverified->databaseValue(),
      ]);

    if ($verifications === []) {
      throw new NoPhoneNumberVerificationByCodeExists($verificationCode);
    }

    $verification = $verifications[\array_key_first($verifications)];
    $this->internalVerify($verification);
  }

  private function internalVerify(PhoneNumberVerificationInterface $verification): void {
    if ($this->enforceUniquePhoneNumbers) {
      /** @var \Drupal\sms\Entity\PhoneNumberVerificationInterface[] $otherVerifications */
      $otherVerifications = $this->phoneNumberVerificationStorage()
        ->loadByProperties([
          'phone' => $verification->getPhoneNumber()->getPhoneNumber(),
          'status' => Verified::Verified->databaseValue(),
        ]);
      foreach ($otherVerifications as $otherVerification) {
        $this->deleteVerification($otherVerification);
      }
    }

    $verification
      ->setStatus(Verified::Verified)
      ->clearVerificationCode()
      ->save();

    $this->logger?->info('Verified verification associated with @for @phone_number (#@id)', [
      '@for' => (string) LogReference::create($verification->getFor()),
      '@id' => $verification->id(),
      '@phone_number' => (string) $verification->getPhoneNumber(),
    ]);
  }

  public function purgeExpiredVerifications(): void {
    $max = $this->now()->modify(\sprintf('-%s seconds', $this->unverifiedLifetime));

    $ids = $this->phoneNumberVerificationStorage()
      ->getQuery()
      ->condition('status', Verified::Unverified->databaseValue())
      ->condition('created', $max->getTimestamp(), '<')
      ->accessCheck(FALSE)
      ->execute();

    foreach ($this->iteratorFactory->get(entityType: PhoneNumberVerification::class, entityIds: $ids) as $verification) {
      $this->deleteVerification($verification);
    }
  }

  private function deleteVerification(PhoneNumberVerificationInterface $verification): void {
    $this->queueFactory->get(DeletePhoneVerification::PLUGIN_ID, reliable: FALSE)
      ->createItem(
        DeletePhoneVerification::createItemFrom($verification),
      );
  }

  public function deletePhoneVerifications(ObjectWithPhoneNumberInterface|EntityInterface $for): void {
    $for = ObjectWithPhoneNumberAdapter::from($for);
    /** @var \Drupal\sms\Entity\PhoneNumberVerificationInterface[] $verifications */
    $verifications = $this->phoneNumberVerificationStorage()
      ->loadByProperties([
        'entity__target_type' => $for->getVerifiedType(),
        'entity__target_id' => $for->getVerifiedIdentifier(),
      ]);
    foreach ($verifications as $verification) {
      $this->deleteVerification($verification);
    }
  }

  private function phoneNumberVerificationStorage(): EntityStorageInterface {
    return $this->entityTypeManager->getStorage(PhoneNumberVerification::ENTITY_TYPE_ID);
  }

  private function now(): \DateTimeImmutable {
    return new \DateTimeImmutable('@' . $this->time->getRequestTime());
  }

}
