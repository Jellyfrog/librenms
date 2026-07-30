<?php

use App\Models\AlertSchedule;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use LibreNMS\Enum\AlertScheduleStatus;

uses(LibreNMS\Tests\DBTestCase::class);

beforeEach(function (): void {
    $this->timezone = config('app.timezone');  //save timezone
});

afterEach(function (): void {
    // revert temp time and timezone
    scheduleSetTimezone($this->timezone);
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

/**
 * Set the test time
 *
 * @param  Carbon|CarbonImmutable  $time
 */
function scheduleSetTestNow($time)
{
    Carbon::setTestNow($time);
    CarbonImmutable::setTestNow($time);
}

function scheduleSetTimezone($timezone)
{
    config(['app.timezone' => $timezone]);
    date_default_timezone_set($timezone);
}

function assertScheduleActive($time, $schedule)
{
    scheduleSetTestNow($time);
    test()->assertEquals(AlertScheduleStatus::ACTIVE, $schedule->status, "$schedule is not active at $time (code)");
    test()->assertTrue(AlertSchedule::where('schedule_id', $schedule->schedule_id)->isActive()->exists(), "$schedule is not active at $time (sql)");
}

function assertScheduleSet($time, $schedule)
{
    scheduleSetTestNow($time);
    test()->assertEquals(AlertScheduleStatus::SET, $schedule->status, "$schedule is not set at $time (code)");
    test()->assertFalse(AlertSchedule::where('schedule_id', $schedule->schedule_id)->isActive()->exists(), "$schedule is not set at $time (sql)");
}

function assertScheduleLapsed($time, $schedule)
{
    scheduleSetTestNow($time);
    test()->assertEquals(AlertScheduleStatus::LAPSED, $schedule->status, "$schedule is not lapsed at $time (code)");
    test()->assertFalse(AlertSchedule::where('schedule_id', $schedule->schedule_id)->isActive()->exists(), "$schedule is not lapsed at $time (sql)");
}

test('normal', function (): void {
    $now = CarbonImmutable::now();

    $schedule = AlertSchedule::factory()->make(); /** @var AlertSchedule $schedule */
    $schedule->start = $now->subHour();
    $schedule->end = $now->addHour();
    $schedule->save();

    scheduleSetTimezone('UTC');
    assertScheduleActive($now, $schedule);
    assertScheduleLapsed($now->addHours(2), $schedule);
    assertScheduleLapsed($now->addDays(10), $schedule);
    assertScheduleSet($now->subHours(2), $schedule);
    assertScheduleSet($now->subDays(10), $schedule);

    scheduleSetTimezone('America/New_York');
    $schedule = $schedule->fresh();
    assertScheduleActive($now, $schedule);
    assertScheduleLapsed($now->addHours(2), $schedule);
    assertScheduleSet($now->subHours(2), $schedule);
});

test('recurring normal', function (): void {
    scheduleSetTimezone('America/New_York');
    $schedule = AlertSchedule::factory()->recurring()->make();
    $schedule->recurring_day = '1,2,3,4,5';
    $schedule->start = Carbon::parse('2020-09-10 2:00');
    $schedule->end = Carbon::parse('9000-09-09 20:00');
    $schedule->save();

    assertScheduleActive(Carbon::parse('2020-09-10 2:01'), $schedule);
    assertScheduleActive(Carbon::parse('2020-09-10 2:00'), $schedule);
    assertScheduleSet(Carbon::parse('2020-09-10 1:59'), $schedule);
    assertScheduleActive(Carbon::parse('2020-09-10 19:59'), $schedule);
    assertScheduleSet(Carbon::parse('2020-09-10 20:01'), $schedule);
    assertScheduleSet(Carbon::parse('2020-09-11 01:00'), $schedule);
    assertScheduleActive(Carbon::parse('2020-09-11 11:00'), $schedule);
    assertScheduleSet(Carbon::parse('2020-09-12 11:00'), $schedule);
    assertScheduleActive(Carbon::parse('2020-09-14 10:00'), $schedule);

    assertScheduleLapsed(Carbon::parse('9999-09-09 20:00'), $schedule);
});
