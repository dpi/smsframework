<?php

/**
 * @file
 * Contains \Drupal\Tests\sms_user\Kernel\SmsFrameworkUserActiveHoursServiceTest.
 */

namespace Drupal\Tests\sms_user\Kernel;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\sms\Kernel\SmsFrameworkKernelBase;
use Drupal\user\Entity\User;

/**
 * Tests active hours service.
 *
 * Using absolute dates to prevent random test failures.
 *
 * @group SMS Framework
 */
class SmsFrameworkUserActiveHoursServiceTest extends SmsFrameworkKernelBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['sms', 'sms_user', 'user'];

  /**
   * @var \Drupal\sms_user\ActiveHoursInterface
   *
   * The active hours service.
   */
  protected $activeHoursService;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->activeHoursService = $this->container->get('sms_user.active_hours');
    $this->installEntitySchema('user');
  }

  /**
   * Test if in hours if active hours is disabled and out of active hours.
   */
  public function testInHoursActiveHoursOff() {
    $this->activeHoursStatus(FALSE);
    $this->setActiveHours([['start' => '2016-03-13 sunday 9:00', 'end' => '2016-03-13 sunday 17:00']]);
    $user = $this->createUser(['timezone' => 'America/New_York']);

    $now = '2016-03-13 Sunday 10pm America/New_York';
    $this->assertTrue($this->activeHoursService->inHours($user, $now));
  }

  /**
   * Test if in hours with different timezone.
   */
  public function testInHoursDifferentTimezone() {
    $this->activeHoursStatus(TRUE);
    $this->setActiveHours([['start' => '2016-03-13 sunday 9:00', 'end' => '2016-03-13 sunday 17:00']]);
    $user = $this->createUser(['timezone' => 'America/New_York']);

    $now = '2016-03-13 Sunday 4pm America/Los_Angeles';
    $this->assertFalse($this->activeHoursService->inHours($user, $now));
  }

  /**
   * Test if in hours with same timezone.
   */
  public function testInHoursSameTimezone() {
    $this->activeHoursStatus(TRUE);
    $this->setActiveHours([['start' => '2016-03-13 sunday 9:00', 'end' => '2016-03-13 sunday 17:00']]);
    $user = $this->createUser(['timezone' => 'America/New_York']);

    $now = '2016-03-13 Sunday 4pm America/New_York';
    $this->assertTrue($this->activeHoursService->inHours($user, $now));
  }

  /**
   * Test if in hours with day not in active hours.
   */
  public function testInHoursDifferentDay() {
    $this->activeHoursStatus(TRUE);
    $this->setActiveHours([['start' => '2016-03-14 monday 9:00', 'end' => '2016-03-14 monday 17:00']]);
    $user = $this->createUser(['timezone' => 'America/New_York']);

    $now = '2016-03-13 Sunday 12pm America/New_York';
    $this->assertFalse($this->activeHoursService->inHours($user, $now));
  }

  /**
   * Test if in hours for 24 hours with same timezone.
   */
  public function testInHoursAllDay() {
    $this->activeHoursStatus(TRUE);
    $this->setActiveHours([['start' => '2016-03-16 wednesday', 'end' => '2016-03-16 wednesday +1 day']]);
    $user = $this->createUser(['timezone' => 'America/New_York']);

    $now = '2016-03-15 Tuesday 8pm America/New_York';
    $this->assertFalse($this->activeHoursService->inHours($user, $now));
    $now = '2016-03-15 Tuesday 11:59:59pm America/New_York';
    $this->assertFalse($this->activeHoursService->inHours($user, $now));
    $now = '2016-03-16 Wednesday 12am America/New_York';
    $this->assertTrue($this->activeHoursService->inHours($user, $now));
    $now = '2016-03-16 Wednesday 12pm America/New_York';
    $this->assertTrue($this->activeHoursService->inHours($user, $now));
    $now = '2016-03-16 Wednesday 11:59pm America/New_York';
    $this->assertTrue($this->activeHoursService->inHours($user, $now));
    $now = '2016-03-17 Thursday 12am America/New_York';
    $this->assertTrue($this->activeHoursService->inHours($user, $now));
    $now = '2016-03-17 Thursday 12:01:01am America/New_York';
    $this->assertFalse($this->activeHoursService->inHours($user, $now));
  }

  /**
   * Test if in hours for 24 hours with different timezone.
   */
  public function testInHoursAllDayDifferentTimezone() {
    $this->activeHoursStatus(TRUE);
    $this->setActiveHours([['start' => '2016-03-16 wednesday', 'end' => '2016-03-16 wednesday +1 day']]);
    $user = $this->createUser(['timezone' => 'America/New_York']);

    $now = '2016-03-15 Tuesday 8pm America/Los_Angeles';
    $this->assertFalse($this->activeHoursService->inHours($user, $now));
    $now = '2016-03-15 Tuesday 11:59:59pm America/Los_Angeles';
    $this->assertTrue($this->activeHoursService->inHours($user, $now));
    $now = '2016-03-16 Wednesday 12am America/Los_Angeles';
    $this->assertTrue($this->activeHoursService->inHours($user, $now));
    $now = '2016-03-16 Wednesday 12pm America/Los_Angeles';
    $this->assertTrue($this->activeHoursService->inHours($user, $now));
    $now = '2016-03-16 Wednesday 11:59pm America/Los_Angeles';
    $this->assertFalse($this->activeHoursService->inHours($user, $now));
    $now = '2016-03-17 Thursday 12am America/Los_Angeles';
    $this->assertFalse($this->activeHoursService->inHours($user, $now));
    $now = '2016-03-17 Thursday 12:01:01am America/Los_Angeles';
    $this->assertFalse($this->activeHoursService->inHours($user, $now));
  }

  /**
   * Test no next time when no ranges are set.
   */
  public function testFindNextTimeNoRanges() {
    $user = $this->createUser();
    $this->assertFalse($this->activeHoursService->findNextTime($user));
  }

  /**
   * Test getting the range for today when within todays range.
   */
  public function testFindNextTimeSameDay() {
    $this->setActiveHours([
      ['start' => '2016-03-15 tuesday 9:00', 'end' => '2016-03-15 tuesday 17:00'],
      ['start' => '2016-03-16 wednesday 9:00', 'end' => '2016-03-16 wednesday 17:00'],
      ['start' => '2016-03-17 thursday 9:00', 'end' => '2016-03-17 thursday 17:00'],
    ]);
    $user = $this->createUser(['timezone' => 'America/New_York']);
    $now = '2016-03-16 Wednesday 1pm America/New_York';
    $range = $this->activeHoursService->findNextTime($user, $now);
    $this->assertEquals(new DrupalDateTime('2016-03-16 Wednesday 9am America/New_York'), $range->getStartDate());
    $this->assertEquals(new DrupalDateTime('2016-03-16 Wednesday 5pm America/New_York'), $range->getEndDate());
  }

  /**
   * Test getting a range for next day when out of hours when a range was today.
   */
  public function testFindNextTimeSameDayOutOfHours() {
    $this->setActiveHours([
      ['start' => '2016-03-15 tuesday 9:00', 'end' => '2016-03-15 tuesday 17:00'],
      ['start' => '2016-03-12 saturday 9:00', 'end' => '2016-03-12 saturday 17:00'],
      ['start' => '2016-03-13 sunday 9:00', 'end' => '2016-03-13 sunday 17:00'],
    ]);
    $user = $this->createUser(['timezone' => 'America/New_York']);
    $now = '2016-03-12 saturday 5:00:01pm America/New_York';
    $range = $this->activeHoursService->findNextTime($user, $now);
    $this->assertEquals(new DrupalDateTime('2016-03-13 Sunday 9am America/New_York'), $range->getStartDate());
    $this->assertEquals(new DrupalDateTime('2016-03-13 Sunday 5pm America/New_York'), $range->getEndDate());
  }

  public function testGetRanges() {
    $this->setActiveHours([
      ['start' => '2016-03-15 tuesday 9:00', 'end' => '2016-03-15 tuesday 17:00'],
      ['start' => '2016-03-16 wednesday 9:00', 'end' => '2016-03-16 wednesday 17:00'],
    ]);

    $ranges = $this->activeHoursService->getRanges('America/New_York');
    // Need to test timezone is same as well, as data objects will compare
    // equality across timezones.
    $this->assertEquals(new DrupalDateTime('2016-03-15 tuesday 9am America/New_York'), $ranges[0]->getStartDate());
    $this->assertEquals('America/New_York', $ranges[0]->getStartDate()->getTimezone()->getName());
    $this->assertEquals(new DrupalDateTime('2016-03-15 tuesday 5pm America/New_York'), $ranges[0]->getEndDate());
    $this->assertEquals('America/New_York', $ranges[0]->getEndDate()->getTimezone()->getName());
    $this->assertEquals(new DrupalDateTime('2016-03-16 wednesday 9am America/New_York'), $ranges[1]->getStartDate());
    $this->assertEquals('America/New_York', $ranges[1]->getStartDate()->getTimezone()->getName());
    $this->assertEquals(new DrupalDateTime('2016-03-16 wednesday 5pm America/New_York'), $ranges[1]->getEndDate());
    $this->assertEquals('America/New_York', $ranges[1]->getEndDate()->getTimezone()->getName());
  }

  /**
   * Helper to set status of active hours.
   */
  protected function activeHoursStatus($status) {
    \Drupal::configFactory()
      ->getEditable('sms_user.settings')
      ->set('active_hours.status', $status)
      ->save();
  }

  /**
   * Helper to set and replace existing active hours ranges.
   */
  protected function setActiveHours($ranges) {
    \Drupal::configFactory()
      ->getEditable('sms_user.settings')
      ->set('active_hours.ranges', $ranges)
      ->save();
  }

  /**
   * Creates a user.
   *
   * @param array $values
   *   Optional settings to add to user.
   *
   * @return \Drupal\user\UserInterface
   *   A saved user entity.
   */
  protected function createUser($values = []) {
    $user = User::create([
      'uid' => 1,
      'name' => $this->randomMachineName(),
    ] + $values);
    $user->save();
    return $user;
  }

}
