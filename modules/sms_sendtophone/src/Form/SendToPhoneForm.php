<?php

declare(strict_types=1);

namespace Drupal\sms_sendtophone\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\node\Entity\Node;
use Drupal\sms\Direction;
use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Provider\PhoneNumberProviderInterface;
use Drupal\sms\Provider\SmsProviderInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default controller for the sms_sendtophone module.
 */
class SendToPhoneForm extends FormBase {

  /**
   * Phone numbers for the authenticated user.
   *
   * @var array
   */
  protected $phoneNumbers = [];

  /**
   * Creates an new SendForm object.
   */
  final public function __construct(
    private readonly SmsProviderInterface $smsProvider,
    private readonly PhoneNumberProviderInterface $phoneNumberProvider,
    MessengerInterface $messenger,
  ) {
    $this->setMessenger($messenger);
  }

  final public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('sms.provider'),
      $container->get('sms.phone_number'),
      $container->get('messenger'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?string $type = NULL, ?int $extra = NULL): array {
    /** @var \Drupal\user\UserInterface $user */
    $user = User::load($this->currentUser()->id());

    // @todo This block should be a route access checker.
    $this->phoneNumbers = $this->phoneNumberProvider->getPhoneNumbers($user);

    if ($user->hasPermission('send to any number') || \count($this->phoneNumbers) > 0) {
      $form = $this->getForm($form, $type, $extra);
    }
    else {
      // User has no phone number, or unconfirmed.
      $form['message'] = [
        '#type' => 'markup',
        '#markup' => $this->t('You need to @setup and confirm your mobile phone to send messages.', [
          '@setup' => $user->toLink('set up', 'edit-form')->toString(),
        ]),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_sendtophone_form';
  }

  /**
   * Builds the form array.
   *
   * @phpstan-return array<string, mixed>
   */
  protected function getForm(array $form, ?string $type = NULL, ?int $extra = NULL): array {
    switch ($type) {
      case 'cck':
      case 'field':
      case 'inline':
        $form['message'] = [
          '#type' => 'value',
          '#value' => $this->getRequest()->get('text'),
        ];
        $form['message_preview'] = [
          '#type' => 'item',
          '#markup' => '<p class="sms-sendtophone--message-preview">' . $this->getRequest()->get('text') . '</p>',
          '#title' => $this->t('Message preview'),
        ];
        break;

      case 'node':
        if (\is_numeric($extra)) {
          $node = Node::load($extra);
          $form['message_display'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Message preview'),
            '#description' => $this->t('This URL will be sent to the phone.'),
            '#cols' => 35,
            '#rows' => 2,
            '#attributes' => ['disabled' => TRUE],
            '#default_value' => $node->toUrl()->setAbsolute()->toString(),
          ];
          $form['message'] = [
            '#type' => 'value',
            '#value' => $node->toUrl()->setAbsolute()->toString(),
          ];
        }
        break;
    }

    $form['number'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone number'),
    ];

    if (\count($this->phoneNumbers) > 0) {
      $form['number']['#default_value'] = \reset($this->phoneNumbers);
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
      '#weight' => 20,
    ];

    // Add library for CSS styling.
    $form['#attached']['library'] = 'sms_sendtophone/default';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $user = User::load($this->currentUser()->id()) ?? throw new \LogicException('Always expect a user');
    /** @var string $number */
    $number = $form_state->getValue('number');
    /** @var string $message */
    $message = $form_state->getValue('message');

    $sms_message = SmsMessage::create()
      ->setDirection(Direction::OUTGOING)
      ->setMessage($message)
      ->setSenderEntity($user)
      ->addRecipient($number);

    try {
      $this->smsProvider->queue($sms_message);
      $this->messenger()->addMessage($this->t('Message has been sent.'));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Message could not be sent: @error', [
        '@error' => $e->getMessage(),
      ]));
    }
  }

}
