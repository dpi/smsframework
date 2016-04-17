<?php

/**
 * @file
 * Contains \Drupal\sms_sendtophone\Tests\SmsSendToPhoneWebTest
 */

namespace Drupal\sms_sendtophone\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\sms\Tests\SmsFrameworkWebTestBase;
use Drupal\sms\Entity\PhoneNumberSettings;

/**
 * Integration tests for the SMS SendToPhone Module.
 * 
 * @group SMS Framework
 */
class SmsSendToPhoneWebTest extends SmsFrameworkWebTestBase {

  public static $modules = ['sms', 'sms_user', 'sms_sendtophone', 'sms_test_gateway', 'node', 'field', 'field_ui'];

  /**
   * @var \Drupal\field\FieldStorageConfigInterface
   */
  protected $phoneField;

  /**
   * @var \Drupal\sms\Entity\PhoneNumberSettingsInterface
   */
  protected $phoneNumberSettings;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array(
        'type' => 'page',
        'name' => 'Basic page',
        'display_submitted' => FALSE,
      ));
      $this->drupalCreateContentType(array(
        'type' => 'article',
        'name' => 'Article'
      ));
    }

    $this->gateway = $this->createMemoryGateway(['skip_queue' => TRUE]);
    $this->defaultSmsProvider
      ->setDefaultGateway($this->gateway);

    $this->phoneField = FieldStorageConfig::create([
      'entity_type' => 'user',
      'field_name' => Unicode::strtolower($this->randomMachineName()),
      'type' => 'telephone',
    ]);
    $this->phoneField->save();

    FieldConfig::create([
      'entity_type' => 'user',
      'bundle' => 'user',
      'field_name' => $this->phoneField->getName(),
    ])->save();

    $this->phoneNumberSettings = PhoneNumberSettings::create();
    $this->phoneNumberSettings
      ->setPhoneNumberEntityTypeId('user')
      ->setPhoneNumberBundle('user')
      ->setFieldName('phone_number', $this->phoneField->getName())
      ->setVerificationMessage($this->randomString())
      ->save();
  }

  /**
   * Tests admin settings page and sendtophone node integration.
   */
  public function testAdminSettingsAndSendToPhone() {
    $user = $this->drupalCreateUser(array('administer smsframework'));
    $this->drupalLogin($user);

    $this->drupalGet('admin/config/smsframework/sendtophone');
    $edit = array();
    $expected = array();
    foreach (NodeType::loadMultiple() as $type) {
      $this->assertText($type->get('name'));
      if (rand(0, 1) > 0.5) {
        $edit["content_types[" . $type->get('type') . "]"] = $expected[$type->get('type')] = $type->get('type');
      }
    }
    // Ensure at least one type is enabled.
    $edit["content_types[page]"] = $expected['page'] = 'page';
    $this->drupalPostForm('admin/config/smsframework/sendtophone', $edit, 'Save configuration');
    $saved = $this->config('sms_sendtophone.settings')->get('content_types', array());
    $this->assertEqual($expected, $saved);

    // Create a new node with sendtophone enabled and verify that the button is
    // added.
    $types = array_keys(array_filter($expected));
    $node = $this->drupalCreateNode(array('type' => $types[0]));
    $this->drupalGet('node/' . $node->id());
    // Confirm message for user without confirmed number.
    $this->assertText(t('Set up and confirm your mobile number to send to phone.'));

    // Confirm phone number.
    $phone_number = $this->randomPhoneNumbers(1)[0];
    $user->{$this->phoneField->getName()} = [$phone_number];
    $user->save();
    $this->verifyPhoneNumber($user, $phone_number);

    $this->drupalGet('node/' . $node->id());
    // Confirm message for user without confirmed number.
    $this->assertText('Send to phone');
    $this->assertFieldByXPath('//a[@title="Send a link via SMS." and @class="sms-sendtophone"]', NULL);

    // Navigate to the "Send to phone" link.
    $this->clickLink('Send to phone');
    $this->assertResponse(200);

    $this->assertFieldByName('number', $phone_number);
    $this->assertFieldByName('message_display', $node->toUrl()->setAbsolute()->toString());

    // Click the send button there.
    $this->drupalPostForm(NULL, ['number' => $phone_number], t('Send'));

    $sms_message = $this->getLastTestMessage($this->gateway);
    $this->assertTrue(in_array($phone_number, $sms_message->getRecipients()));
    $this->assertEqual($sms_message->getMessage(), Url::fromUri('entity:node/' . $node->id(), array('absolute' => true))->toString());
  }

  /**
   * Tests sendtophone filter integration.
   */
  public function testSendToPhoneFilter() {
    $user = $this->drupalCreateUser(['administer filters']);
    $this->drupalLogin($user);

    $edit = array(
      'filters[filter_inline_sms][status]' => true,
      'filters[filter_inline_sms][settings][display]' => 'text',
    );
    $this->drupalPostForm('admin/config/content/formats/manage/plain_text', $edit, t('Save configuration'));
    // Create a new node sms markup and verify that a link is created.
    $type_names = array_keys(NodeType::loadMultiple());
    $node_body = $this->randomMachineName(30);
    $node = $this->drupalCreateNode(array(
      'type' => array_pop($type_names),
      'body' => array(array(
        'value' => "[sms]{$node_body}[/sms]",
        'format' => 'plain_text',
      )),
    ));

    // Unconfirmed users
    $this->drupalGet('sms/sendtophone/inline');
    $this->assertText('You need to set up and confirm your mobile phone to send messages');

    // Confirm phone number.
    $phone_number = $this->randomPhoneNumbers(1)[0];
    $user->{$this->phoneField->getName()} = [$phone_number];
    $user->save();
    $this->verifyPhoneNumber($user, $phone_number);

    $this->drupalGet('node/' . $node->id());
    // Confirm link was created for Send to phone.
    $this->assertText("$node_body (Send to phone)");

    $this->clickLink('(Send to phone)');
    $this->assertResponse(200);
    $this->assertText($node_body);

    // Submit phone number and confirm message received.
    $this->drupalPostForm(NULL, array(), t('Send'), array(
      'query' => array(
        'text' => $node_body,
        'destination' => 'node/' . $node->id(),
      )
    ));

    $sms_message = $this->getLastTestMessage($this->gateway);
    $this->assertEqual($sms_message->getMessage(), $node_body, 'Message body "' . $node_body . '" successfully sent.');
  }

  /**
   * Tests field format integration and widget.
   */
  public function testFieldFormatAndWidget() {
    // Create a custom field of type 'text' using the sms_sendtophone formatter.
    $bundles = array_keys(NodeType::loadMultiple());
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_definition = array(
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => $bundles[0],
      // Need to verify this.
//      'display' => array(
//        'teaser' => array(
//          'type' => 'sms_link',
//        )
//      ),
    );
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'text',
    ));
    $field_storage->save();
    $field = FieldConfig::create($field_definition);
    $field->save();

    $display = EntityViewDisplay::load('node.' . $bundles[0] . '.default');
    if (!$display) {
      $display = EntityViewDisplay::create(array(
        'targetEntityType' => 'node',
        'bundle' => $bundles[0],
        'mode' => 'default',
        'status' => TRUE,
      ));
    }
    $display->setComponent($field_name)->save();
    $random_text = $this->randomMachineName(32);
    $test_node = $this->drupalCreateNode([
      'type' => $bundles[0],
      $field_name => [[
        'value' => $random_text,
      ]],
    ]);
    // This is a quick-fix. Need to find out how to add display filters from code.
    $this->drupalLogin($this->rootUser);
    $this->drupalPostForm('admin/structure/types/manage/article/display', ['fields['. $field_name . '][type]' => 'sms_link'], 'Save');

    // Confirm phone number.
    $user = $this->drupalCreateUser();
    $phone_number = $this->randomPhoneNumbers(1)[0];
    $user->{$this->phoneField->getName()} = [$phone_number];
    $user->save();
    $this->verifyPhoneNumber($user, $phone_number);
    $this->drupalLogin($user);

    // Click send button.
    $this->drupalGet('node/' . $test_node->id());
    $this->assertText($random_text, 'Field format works');
    $this->assertText($random_text . ' (Send to phone)');
    $this->clickLink('Send to phone');

    // Click the send button there.
    $this->drupalPostForm(NULL, [], 'Send', array('query' => array('text' => $random_text)));

    $sms_message = $this->getLastTestMessage($this->gateway);
    $this->assertTrue(in_array($phone_number, $sms_message->getRecipients()), 'Message sent to correct number');
    $this->assertEqual($sms_message->getMessage(), $random_text, 'Field content sent to user');
  }

}
