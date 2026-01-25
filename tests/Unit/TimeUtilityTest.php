<?php

use Illuminate\Support\Carbon;
use LibreNMS\Util\Time;

test('formatInterval', function () {
    expect(Time::formatInterval(0))->toBe('');
    expect(Time::formatInterval(null))->toBe('');
    expect(Time::formatInterval(1))->toBe('1 second');
    expect(Time::formatInterval(3, true))->toBe('3s');
    expect(Time::formatInterval(60))->toBe('1 minute');
    expect(Time::formatInterval(-60))->toBe('1 minute ago');
    expect(Time::formatInterval(61, true))->toBe('1m 1s');
    expect(Time::formatInterval(60 * 60))->toBe('1 hour');
    expect(Time::formatInterval(24 * 60 * 60))->toBe('1 day');
    expect(Time::formatInterval(17 * 24 * 60 * 60 + 1456))->toBe('2 weeks 3 days 24 minutes 16 seconds');
    expect(Time::formatInterval(17 * 24 * 60 * 60 + 1456, parts: 2))->toBe('2 weeks 3 days');

    // different months could change this
    $this->travelTo(Carbon::createFromTimestampUTC(30042), function (): void {
        expect(Time::formatInterval(39 * 24 * 60 * 60 + 1456))->toBe('1 month 1 week 2 days 24 minutes');
        expect(Time::formatInterval(39 * 24 * 60 * 60 + 1456, true, 5))->toBe('1mo 1w 2d 24m 16s');
    });

    // calculate if there is a leap year (could freeze time, try this instead)
    if (Carbon::createFromDate(Carbon::now()->year, 2, 28)->isPast()) {
        $days = Carbon::now()->isLeapYear() ? 366 : 365;
    } else {
        $days = Carbon::now()->subYear()->isLeapYear() ? 366 : 365;
    }

    expect(Time::formatInterval($days * 24 * 60 * 60))->toBe('1 year');
    expect(Time::formatInterval(-$days * 24 * 60 * 60))->toBe('1 year ago');

    expect(Time::formatInterval(1461 * 24 * 60 * 60))->toBe('4 years');
});

test('parseAtTime', function () {
    expect(Time::parseAt('now'))->toBe(time());
    expect(Time::parseAt('+3m'))->toBe(time() + 180);
    expect(Time::parseAt('+2h'))->toBe(time() + 7200);
    expect(Time::parseAt('+2d'))->toBe(time() + 172800);
    expect(Time::parseAt('+2y'))->toBe(time() + 63115200);
    expect(Time::parseAt('-3m'))->toBe(time() - 180);
    expect(Time::parseAt('-2h'))->toBe(time() - 7200);
    expect(Time::parseAt('-2d'))->toBe(time() - 172800);
    expect(Time::parseAt('-2y'))->toBe(time() - 63115200);
    expect(Time::parseAt('429929439'))->toBe(429929439);
    expect(Time::parseAt(212334234))->toBe(212334234);
    expect(Time::parseAt('-43'))->toBe(time() - 43);
    expect(Time::parseAt('invalid'))->toBe(0);
    expect(Time::parseAt('March 23 1989 UTC'))->toBe(606614400);
    expect(Time::parseAt('+1 day'))->toBe(time() + 86400);
});

test('parseInput', function () {
    expect(Time::parseInput(null))->toBeNull();
    expect(Time::parseInput(''))->toBeNull();

    expect(Time::parseInput(315532800))->toBe(315532800);

    $this->travelTo(Carbon::createFromTimestampUTC(1700000000), function (): void {
        $now = 1700000000;
        expect(Time::parseInput('10m'))->toBe($now - 600);
        expect(Time::parseInput('+1h'))->toBe($now + 3600);
        expect(Time::parseInput('-1d'))->toBe($now - 86400);
        expect(Time::parseInput('-1w'))->toBe($now - 604800);
        expect(Time::parseInput('+1y'))->toBe($now + 31622400);
    });

    $expected = Carbon::parse('2023-01-02 03:04:05 UTC')->getTimestamp();
    expect(Time::parseInput('2023-01-02 03:04:05 UTC'))->toBe($expected);

    expect(Time::parseInput('not a date'))->toBeNull();
});

test('randomTimeBetween', function () {
    $start = time();
    $end = time() + 3600;
    $randomTime = Time::randomBetween($start, $end)->format('U');
    expect($randomTime)->toBeGreaterThanOrEqual($start);
    expect($randomTime)->toBeLessThanOrEqual($end);

    // test with pseudo random
    $randomTime = Time::pseudoRandomBetween($start, $end, 'U');
    expect($randomTime)->toBeGreaterThanOrEqual($start);
    expect($randomTime)->toBeLessThanOrEqual($end);

    expect(Time::pseudoRandomBetween($start, $end))
        ->toEqual(Time::pseudoRandomBetween($start, $end));
});
