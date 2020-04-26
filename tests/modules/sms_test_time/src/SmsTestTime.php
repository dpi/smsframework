<?php

namespace Drupal\sms_test_time;

use Drupal\Component\Datetime\TimeInterface;

/**
 * Time service with a predictable time.
 */
class SmsTestTime implements TimeInterface {

  /**
   * Original time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $original;

  /**
   * Date for testing.
   *
   * @var \DateTime
   */
  protected $date;

  /**
   * SmsTestTime constructor.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $original
   *   Original time service.
   */
  public function __construct(TimeInterface $original) {
    $this->original = $original;
    $this->date = new \DateTimeImmutable('2:30pm 17 October 1997', new \DateTimeZone('UTC'));
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestTime() {
    return $this->date->format('U');
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestMicroTime() {
    return $this->date->format('U.u');
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentTime() {
    return $this->date->format('U');
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentMicroTime() {
    return $this->date->format('U.u');
  }

}
