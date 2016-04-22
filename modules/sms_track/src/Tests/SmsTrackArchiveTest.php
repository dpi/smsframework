<?php

/**
 * @file
 * Contains \Drupal\sms_track\Tests\SmsTrackArchiveTest.
 */

namespace Drupal\sms_track\Tests;

use Drupal\Component\Utility\Xss;
use Drupal\simpletest\WebTestBase;

/**
 * Integration tests for the SMS Framework Track Module.
 *
 * @group SMS Framework
 */
class SmsTrackArchiveTest extends WebTestBase {

  protected static $modules = ['sms', 'sms_test_gateway', 'sms_track', 'sms_devel'];

  /**
   * Tests recording a message sent from one site user to another.
   */
  public function testArchiveWriteForSentMessages() {

    // Create Author User
    $author = $this->drupalCreateUser(['administer smsframework', 'receive sms', 'edit own sms number', 'view own sms messages', 'view all sms messages']);
    $this->drupalLogin($author);

    $archiving_settings = array(
      'archive_dir' => '4',
    );
    $this->drupalPostForm('admin/config/smsframework/sms_track', $archiving_settings, t('Save'));

    // Confirm author number.
    $edit = array('number' => '1234567890');
    $this->drupalPostForm('user/' . $author->id() . '/mobile', $edit, t('Confirm number'));
    $this->drupalPostForm(NULL, array(), t('Confirm without code'));
    $this->assertText('Your mobile phone number has been confirmed.', 'Authors number is confirmed');

    $this->drupalLogout();

    // Create Recipient User
    $recipient = $this->drupalCreateUser(['administer smsframework', 'receive sms', 'edit own sms number', 'view own sms messages']);
    $this->drupalLogin($recipient);

    // Confirm recipient number.
    $edit = array('number' => '0987654321');
    $this->drupalPostForm('user/' . $recipient->id() . '/mobile', $edit, t('Confirm number'));
    $this->drupalPostForm(NULL, array(), t('Confirm without code'));
    $this->assertText('Your mobile phone number has been confirmed.', 'Recipients number is confirmed');

    $this->drupalLogout();

    $this->drupalLogin($author);

    $test_message = array(
      'number' => '0987654321',
      'message' => 'Test archiving messages from one registered number to another',
    );

   $this->drupalPostForm('admin/config/smsframework/devel', $test_message, t('Send Message'));
   $this->assertResponse(200);
   $this->assertText('Form submitted ok for number ' . $test_message['number'] . ' and message: ' . $test_message['message'], 'Successfully sent message to recipient with registered number');

    // Test whether author and recipient uids were recorded properly.
    $this->drupalGet('user/' . $author->id() . '/my-messages');
    $this->assertTextInXPath('//tbody/tr[1]/td[1]', $test_message['message'],
      'Message recorded and displayed properly on Author\'s My Messages page.');
    $this->assertTextInXPath('//tbody/tr[1]/td[2]', $author->getUsername(),
      'Author\'s name recorded and displayed properly on Author\'s My Messages page.');
    $this->assertTextInXPath('//tbody/tr[1]/td[3]', $recipient->getUsername(),
      'Recipient\'s name recorded and displayed properly on Author\'s My Messages page.');

    $this->drupalLogout();
    $this->drupalLogin($recipient);

    // Test whether author and recipient uids were recorded properly.
    $this->drupalGet('user/' . $recipient->id() . '/my-messages');
    $this->assertTextInXPath('//tbody/tr[1]/td[1]', $test_message['message'],
      'Message recorded and displayed properly on Recipient\'s My Messages page.');
    $this->assertTextInXPath('//tbody/tr[1]/td[2]', $author->getUsername(),
      'Author\'s name recorded and displayed properly on Recipient\'s My Messages page.');
    $this->assertTextInXPath('//tbody/tr[1]/td[3]', $recipient->getUsername(),
      'Recipient\'s name recorded and displayed properly on Recipient\'s My Messages page.');

    // Test sending messages to unknown number.
    $test_message = array(
      'number' => '23456789103',
      'message' => 'Test archive of message sent to unknown recipient',
    );
    $this->drupalPostForm('admin/config/smsframework/devel', $test_message, t('Send Message'));
    $this->assertResponse(200);
    $this->assertText('Form submitted ok for number ' . $test_message['number'] . ' and message: ' . $test_message['message'], 'Successfully sent message to unknown recipient');

    $this->drupalGet('user/' . $recipient->id() . '/my-messages');
    $this->assertTextInXPath('//tbody/tr[1]/td[1]', $test_message['message'],
      'Message recorded and displayed properly on Author\'s My Messages page.');
    $this->assertTextInXPath('//tbody/tr[1]/td[2]', $recipient->getUsername(),
      'Author\'s name recorded and displayed properly on Author\'s My Messages page.');
    $this->assertTextInXPath('//tbody/tr[1]/td[3]', 'Anonymous (not verified)',
      'Recipient\'s name recorded and displayed properly on Author\'s My Messages page.');

    // Test receiving messages from unknown number.
    $test_message = array(
      'number' => '23456789103',
      'message' => 'Test archive of message received from unknown recipient',
    );
    $this->drupalPostForm('admin/config/smsframework/devel', $test_message, t('Receive Message'));
    $this->assertResponse(200);
    $this->assertText('Message received from number ' . $test_message['number'] . ' and message: ' . $test_message['message'], 'Successfully received message from unknown sender');

    $this->drupalGet('user/' . $recipient->id() . '/my-messages');
    $this->assertTextInXPath('//tbody/tr[1]/td[1]', $test_message['message'],
      'Message recorded and displayed properly on Recipient\'s My Messages page.');
    $this->assertTextInXPath('//tbody/tr[1]/td[2]', 'Anonymous (not verified)',
      'Author\'s name recorded and displayed properly on Recipient\'s My Messages page.');
    $this->assertTextInXPath('//tbody/tr[1]/td[3]', $recipient->getUsername(),
      'Recipient\'s name recorded and displayed properly on Recipient\'s My Messages page.');

    // Test that a user can only view their own messages.
    $this->drupalLogout();
    $this->drupalGet('user/' . $author->id() . '/my-messages');
    $this->assertResponse(403, 'Access denied from anonymous user to My Messages page.');
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    $this->drupalGet('user/' . $author->id() . '/my-messages');
    $this->assertResponse(403, 'Access denied from authenticated user to another user\'s My Messages page.');

    // Test that all messages are available on administrative interface.
    $this->drupalLogin($author);
    $this->drupalGet('admin/config/smsframework/sms_track/view');
    $this->assertEqual(count($this->xpath('//tbody/tr')), 5, 'All messages captured in admin sms_track view');
  }

  /**
   * Asserts that some text exists in the current page following the given XPath.
   *
   * @param string $xpath
   *   XPath used to find the text.
   * @param string $text
   *   The text that is to be checked.
   * @param string $message
   *   (optional) Message to display.
   * @param string $group
   *   (optional) The group this message belongs to.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertTextInXPath($xpath, $text, $message = NULL, $group = 'Other') {
    if (!$message) {
      $message = t('"@text" found', array('@text' => $text));
    }
    /** @var \SimpleXMLElement $xml_node */
    foreach ($this->xpath($xpath) as $xml_node) {
      $xml = Xss::filter($xml_node->asXML(), array());
      if (strpos($xml, $text) !== FALSE) {
        return $this->pass($message, $group);
      }
    };

    return $this->fail($message, $group);
  }

}
