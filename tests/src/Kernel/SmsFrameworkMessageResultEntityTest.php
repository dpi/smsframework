<?php

namespace Drupal\Tests\sms\Kernel;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\sms\Entity\SmsDeliveryReport;
use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Entity\SmsMessageResult;
use Drupal\sms\Message\SmsMessageResultStatus;
use Drupal\Tests\sms\Functional\SmsFrameworkMessageResultTestTrait;

/**
 * Tests the SMS message result entity.
 *
 * @group SMS Framework
 * @group legacy
 * @coversDefaultClass \Drupal\sms\Entity\SmsMessageResult
 */
class SmsFrameworkMessageResultEntityTest extends KernelTestBase {

  use SmsFrameworkMessageResultTestTrait;

  public static $modules = ['user', 'sms', 'sms_test_gateway', 'telephone', 'dynamic_entity_reference', 'entity_test'];

  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installEntitySchema('sms');
    $this->installEntitySchema('sms_result');
    $this->installEntitySchema('sms_report');
  }

  /**
   * {@inheritdoc}
   */
  protected function createMessageResult() {
    return SmsMessageResult::create();
  }

  /**
   * Tests saving and retrieval of complete entity.
   */
  public function testSaveAndRetrieveResult() {
    /** @var \Drupal\sms\Entity\SmsMessageResult $result */
    $result = $this->createMessageResult()
      ->setCreditsUsed(rand(5, 10))
      ->setCreditsBalance(rand(10, 20))
      ->setError(SmsMessageResultStatus::INVALID_SENDER)
      ->setErrorMessage('Invalid sender ID')
      ->setReports([SmsDeliveryReport::create()->setRecipient('1234567890')]);

    // Add the result to an SMS entity and save.
    $sms_message = SmsMessage::create()
      ->addRecipient('1234567890')
      ->setResult($result);
    $sms_message->save();

    $storage = $this->container->get('entity_type.manager')->getStorage('sms_result');
    $saved = $storage->load($result->id());
    /** @var \Drupal\sms\Entity\SmsMessageResult $saved */
    $this->assertEquals($result->getCreditsBalance(), $saved->getCreditsBalance());
    $this->assertEquals($result->getCreditsUsed(), $saved->getCreditsUsed());
    $this->assertEquals($result->getError(), $saved->getError());
    $this->assertEquals($result->getErrorMessage(), $saved->getErrorMessage());
    $this->assertEquals($result->getReports()[0]->getRecipient(), $saved->getReports()[0]->getRecipient());
    $this->assertEquals($result->uuid(), $saved->uuid());
  }

  /**
   * Tests saving a message result without a parent SMS message.
   */
  public function testSaveResultWithoutParent() {
    $this->setExpectedException(EntityStorageException::class, 'No parent SMS message specified for SMS message result');
    /** @var \Drupal\sms\Entity\SmsMessageResult $result */
    $result = $this->createMessageResult()
      ->setCreditsUsed(rand(5, 10))
      ->setCreditsBalance(rand(10, 20))
      ->setError(SmsMessageResultStatus::INVALID_SENDER)
      ->setErrorMessage('Invalid sender ID')
      ->setReports([SmsDeliveryReport::create()->setRecipient('1234567890')]);
    $result->save();
  }

  /**
   * Tests that getReports returns an empty array if no reports are set.
   *
   * @covers ::getReports
   */
  public function testGetReportsNoReport() {
    $result = SmsMessageResult::create();
    $this->assertEquals([], $result->getReports());
  }

}
