<?php

/**
 * @file
 * Contains tests for the sms_blast module.
 */

namespace Drupal\sms_blast\Tests;

use \Drupal\simpletest\WebTestBase;

/**
 * Integration tests for the sms_blast module.
 *
 * @group SMS Framework
 */
class SmsBlastWebTest extends WebTestBase {

  public static $modules = ['sms', 'sms_test_gateway', 'sms_user', 'sms_blast'];

  /**
   * Tests sending sms blast.
   */
  function testSendBlast() {
    // Set up test default gateway and test user.
    $this->container->get('config.factory')->getEditable('sms.settings')->set('default_gateway', 'test')->save();
    $user = $this->drupalCreateUser(array('receive sms', 'Send SMS Blast'));
    $this->drupalLogin($user);
    $data = array(
      'number' => '23415678900',
      'status' => SMS_USER_CONFIRMED,
      'code' => rand(1000, 9999),
      'gateway' => '',
    );
    $user->sms_user = $data;
    $user->save();

    // Post sms blast to registered users.
    $message = $this->randomString(140);
    $this->drupalPostForm('sms_blast', array('message' => $message), t('Send'));
    $this->assertResponse(200);
    $this->assertText('The message was sent to 1 users.', 'Message sent to 1 user.');

    // Get the resulting message that was sent and confirm.
    $this->assertEqual(sms_test_gateway_result()['message'], $message, 'Successfully sent sms blast message');
  }

}
