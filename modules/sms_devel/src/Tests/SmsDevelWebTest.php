<?php

/**
 * @file
 * Tests for the SMS Devel module.
 */

namespace Drupal\sms_devel\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the send/receive form provided by SMS Devel.
 *
 * @group SMS Framework
 */
class SmsDevelWebTest extends WebTestBase {

  public static $modules = ['sms', 'sms_test_gateway', 'sms_devel'];

  /**
   * Returns the config() object.
   *
   * @return \Drupal\Core\Config\Config
   */
  protected function config($name) {
    return $this->container->get('config.factory')->get($name);
  }

  /**
   * Tests if messages sent using the test send form are stored properly.
   */
  public function testDevelSendReceiveForm() {

    // Create privileged user.
    $user = $this->drupalCreateUser(array('administer smsframework'));
    $this->drupalLogin($user);

    // Set up test default gateway.
    $this->config('sms.settings')->set('default_gateway', 'test')->save();

    $test_message1 = array(
      'number' => '1234567890',
      'message' => 'Testing Send Message Button',
    );

    $this->drupalPostForm('admin/config/smsframework/devel', $test_message1, t('Send Message'));
    $this->assertResponse(200);
    $this->assertText('Form submitted ok for number ' . $test_message1['number'] . ' and message: ' . $test_message1['message'], 'Successfully sent message using form.');

    // Check from gateway that the sms got sent. Use array_intersect_assoc() to
    // remove other array elements not needed.
    $result = array_intersect_assoc(sms_test_gateway_result(), $test_message1);
    $this->assertEqual($result, $test_message1, 'Message was sent correctly using sms_devel.');

    $test_message2 = array(
      'number' => '0987654321',
      'message' => 'Testing Receive Message Button',
    );

    $this->drupalPostForm('admin/config/smsframework/devel', $test_message2, t('Receive Message'));
    $this->assertResponse(200);
    $this->assertText('Message received from number ' . $test_message2['number'] . ' and message: ' . $test_message2['message'], 'Successfully received message using form.');

    // Use sms_test_gateway_get_incoming to get the incoming sms.
    $result = array_intersect_assoc(sms_test_gateway_get_incoming('process'), $test_message2);
    $this->assertEqual($result, $test_message2, 'Message was received correctly using sms_devel.');
  }
}
