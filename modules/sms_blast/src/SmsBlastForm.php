<?php

declare(strict_types=1);

namespace Drupal\sms_blast;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\sms\Message\SmsMessage;
use Drupal\sms\Provider\PhoneNumberProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form for sending mass messages.
 */
class SmsBlastForm extends FormBase {

  final public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly PhoneNumberProviderInterface $phoneNumberProvider,
    MessengerInterface $messenger,
  ) {
    $this->setMessenger($messenger);
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('sms.phone_number'),
      $container->get('messenger'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_blast_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['message'] = [
      '#type'  => 'textarea',
      '#title' => $this->t('Message'),
      '#cols'  => 60,
      '#rows'  => 5,
    ];

    $form['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Send'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var string $message */
    $message = $form_state->getValue('message');
    $sms_message = (new SmsMessage())
      ->setMessage($message);

    $ids = $this->phoneNumberVerificationStorage()->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('entity__target_type', 'user')
      ->execute();

    $success = 0;
    $failure = 0;
    $entity_ids = [];
    /** @var \Drupal\sms\Entity\PhoneNumberVerificationInterface $verification */
    foreach ($this->phoneNumberVerificationStorage()->loadMultiple($ids) as $verification) {
      // Ensure entity exists and only one message is sent to each entity.
      $entity = $verification->getEntity();
      if ($entity !== NULL && !\in_array($entity->id(), $entity_ids, TRUE)) {
        $entity_ids[] = $entity->id();

        try {
          $this->phoneNumberProvider
            ->sendMessage($entity, $sms_message);
          $success++;
        }
        catch (\Exception) {
          $failure++;
        }
      }
    }

    if ($success > 0) {
      $this->messenger()->addMessage($this->formatPlural($success, 'Message sent to @count user.', 'Message sent to @count users.'));
    }
    if ($failure > 0) {
      $this->messenger()->addError($this->formatPlural($failure, 'Message could not be sent to @count user.', 'Message could not be sent to @count users.'));
    }
  }

  private function phoneNumberVerificationStorage(): EntityStorageInterface {
    return $this->entityTypeManager->getStorage('sms_phone_number_verification');
  }

}
