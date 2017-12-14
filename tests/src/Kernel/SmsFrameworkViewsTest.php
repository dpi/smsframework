<?php

namespace Drupal\Tests\sms\Kernel;

use Drupal\Tests\sms\Functional\SmsFrameworkTestTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;
use Drupal\views\Tests\ViewTestData;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\sms\Entity\SmsMessage;
use Drupal\Core\Render\RenderContext;
use Drupal\sms\Direction;

/**
 * Tests SMS Framework integration with Views.
 *
 * @group SMS Framework
 * @group legacy
 */
class SmsFrameworkViewsTest extends ViewsKernelTestBase {

  use SmsFrameworkTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user', 'sms', 'sms_test_gateway', 'sms_test_views', 'telephone',
    'dynamic_entity_reference', 'field',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['sms_messages'];

  /**
   * The SMS provider service.
   *
   * @var \Drupal\sms\Provider\SmsProviderInterface
   */
  protected $smsProvider;

  /**
   * A memory gateway.
   *
   * @var \Drupal\sms\Entity\SmsGatewayInterface
   */
  protected $gateway;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installEntitySchema('user');
    $this->installEntitySchema('sms');
    $this->installEntitySchema('sms_result');
    $this->installEntitySchema('sms_report');

    $this->smsProvider = $this->container->get('sms.provider');

    $this->gateway = $this->createMemoryGateway();
    $this->setFallbackGateway($this->gateway);

    ViewTestData::createTestViews(get_class($this), ['sms_test_views']);
  }

  /**
   * Tests view of SMS entities with join to recipient table.
   */
  public function testSms() {
    // Create a role and user which has permission to view the entity links
    // generated for 'gateway', 'sender_entity__target_id', and
    // 'recipient_entity__target_id' columns.
    $role = Role::create(['id' => $this->randomMachineName()]);
    $role->grantPermission('access user profiles');
    $role->grantPermission('administer smsframework');
    $role->save();

    $user0 = User::create(['name' => $this->randomMachineName()]);
    $user0->addRole($role->id());
    $user0->save();

    $this->container->get('current_user')->setAccount($user0);

    // Create some users to associate with SMS messages.
    $user1 = User::create(['name' => $this->randomMachineName()]);
    $user1->save();
    $user2 = User::create(['name' => $this->randomMachineName()]);

    $message1 = SmsMessage::create(['created' => 892818493]);
    /** @var SmsMessageInterface $message1 */
    $message1
      ->setSenderEntity($user1)
      ->addRecipients($this->randomPhoneNumbers(2))
      ->setDirection(Direction::OUTGOING)
      ->setMessage($this->randomMachineName())
      ->setSenderNumber('+123123123')
      ->setQueued(TRUE);
    $this->smsProvider->queue($message1);

    $message2 = SmsMessage::create(['created' => 499488211]);
    $message2
      ->setRecipientEntity($user1)
      ->setSenderEntity($user2)
      ->addRecipients($this->randomPhoneNumbers(1))
      ->setDirection(Direction::INCOMING)
      ->setMessage($this->randomMachineName())
      ->setAutomated(FALSE)
      ->setProcessedTime(499488280)
      ->setGateway($this->gateway);
    $message2->setResult($this->createMessageResult($message2));
    $this->smsProvider->queue($message2);

    Views::viewsData()->clear();

    $view = Views::getView('sms_messages');
    $view->setDisplay('default');
    $this->executeView($view);

    $this->assertEquals(2, $view->total_rows);

    $cols = [
      'direction_1', 'sender_phone_number', 'recipient_phone_number',
      'message', 'created', 'gateway', 'sender_entity__target_id',
      'recipient_entity__target_id', 'automated', 'processed', 'queued',
    ];
    $this->assertEquals($cols, array_keys($view->field));

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    // direction_1.
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message1) {
      return $view->field['direction_1']->advancedRender($view->result[0]);
    });
    $this->assertEquals('Outgoing', $render);

    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message2) {
      return $view->field['direction_1']->advancedRender($view->result[1]);
    });
    $this->assertEquals('Incoming', $render);

    // sender_phone_number.
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message1) {
      return $view->field['sender_phone_number']->advancedRender($view->result[0]);
    });
    $this->assertEquals($message1->getSenderNumber(), $render);

    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message2) {
      return $view->field['sender_phone_number']->advancedRender($view->result[1]);
    });
    $this->assertEquals('', $render);

    // recipient_phone_number.
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message1) {
      return $view->field['recipient_phone_number']->advancedRender($view->result[0]);
    });

    $number1 = $message1->getRecipients()[0];
    $number2 = $message1->getRecipients()[1];
    $this->assertEquals('<a href="tel:' . urlencode($number1) . '">' . $number1 . '</a>, <a href="tel:' . urlencode($number2) . '">' . $number2 . '</a>', $render);

    $number1 = $message2->getRecipients()[0];
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message2) {
      return $view->field['recipient_phone_number']->advancedRender($view->result[1]);
    });
    $this->assertEquals('<a href="tel:' . urlencode($number1) . '">' . $number1 . '</a>', $render);

    // message.
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message1) {
      return $view->field['message']->advancedRender($view->result[0]);
    });
    $this->assertEquals($message1->getMessage(), $render);

    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message2) {
      return $view->field['message']->advancedRender($view->result[1]);
    });
    $this->assertEquals($message2->getMessage(), $render);

    // created.
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message1) {
      return $view->field['created']->advancedRender($view->result[0]);
    });
    $this->assertEquals('Fri, 04/17/1998 - 23:08', $render);

    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message2) {
      return $view->field['created']->advancedRender($view->result[1]);
    });
    $this->assertEquals('Wed, 10/30/1985 - 13:43', $render);

    // gateway.
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message1) {
      return $view->field['gateway']->advancedRender($view->result[0]);
    });
    $this->assertEquals($this->gateway->toLink(NULL, 'edit-form')->toString(), $render);

    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message2) {
      return $view->field['gateway']->advancedRender($view->result[1]);
    });
    $this->assertEquals($this->gateway->toLink(NULL, 'edit-form')->toString(), $render);

    // sender_entity__target_id.
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message1) {
      return $view->field['sender_entity__target_id']->advancedRender($view->result[0]);
    });
    $this->assertEquals($user1->toLink()->toString(), $render);

    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message2) {
      return $view->field['sender_entity__target_id']->advancedRender($view->result[1]);
    });
    $this->assertEquals($user2->toLink()->toString(), $render);

    // recipient_entity__target_id.
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message1) {
      return $view->field['recipient_entity__target_id']->advancedRender($view->result[0]);
    });
    $this->assertEquals('None', $render);

    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message2) {
      return $view->field['recipient_entity__target_id']->advancedRender($view->result[1]);
    });
    $this->assertEquals($user1->toLink()->toString(), $render);

    // automated.
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message1) {
      return $view->field['automated']->advancedRender($view->result[0]);
    });
    $this->assertEquals('Automated', $render);

    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message2) {
      return $view->field['automated']->advancedRender($view->result[1]);
    });
    $this->assertEquals('Not automated', $render);

    // processed.
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message1) {
      return $view->field['processed']->advancedRender($view->result[0]);
    });
    $this->assertEquals('', $render);

    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message2) {
      return $view->field['processed']->advancedRender($view->result[1]);
    });
    $this->assertEquals('Wed, 10/30/1985 - 13:44', $render);

    // queued.
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message1) {
      return $view->field['queued']->advancedRender($view->result[0]);
    });
    $this->assertEquals('Queued', $render);

    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $message2) {
      return $view->field['queued']->advancedRender($view->result[1]);
    });
    $this->assertEquals('Not queued', $render);
  }

}
