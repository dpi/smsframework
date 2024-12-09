<?php

declare(strict_types=1);

namespace Drupal\sms\EventSubscriber;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sms\Event\SmsDeliveryReportEvent;
use Drupal\sms\Event\SmsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles delivery reports as they come in and updates storage.
 */
class SmsDeliveryReportsProcessor implements EventSubscriberInterface {

  /**
   * Creates a new SmsDeliveryReportsProcessor controller.
   */
  final public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Updates the delivery status on stored SMS delivery reports.
   *
   * @param \Drupal\sms\Event\SmsDeliveryReportEvent $event
   *   The event containing updated delivery reports status.
   */
  public function updateDeliveryReports(SmsDeliveryReportEvent $event): void {
    foreach ($event->getReports() as $report) {
      // Only messages that have message IDs can be tracked and updated.
      if ($report->getMessageId() !== NULL) {
        /** @var \Drupal\sms\Entity\SmsDeliveryReport[] $existing */
        $existing = $this->reportStorage()->loadByProperties(['message_id' => $report->getMessageId()]);
        if ($existing !== []) {
          $existing = \reset($existing);
          $existing
            ->setStatus($report->getStatus())
            ->setStatusMessage($report->getStatusMessage())
            ->setStatusTime($report->getStatusTime())
            ->save();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Update delivery reports as they are received.
    $events = [];
    $events[SmsEvents::DELIVERY_REPORT_POST_PROCESS][] = ['updateDeliveryReports', 1024];
    return $events;
  }

  private function reportStorage(): EntityStorageInterface {
    return $this->entityTypeManager->getStorage('sms_report');
  }

}
