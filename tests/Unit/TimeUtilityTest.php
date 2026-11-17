<?php

use Illuminate\Support\Carbon;
use LibreNMS\Util\Time;

uses(LibreNMS\Tests\TestCase::class);

test('format interval', function (): void {
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

test('parse at time', function (): void {
    expect(Time::parseAt('now'))->toEqual(time(), 'now did not match');
    expect(Time::parseAt('+3m'))->toEqual(time() + 180, '+3m did not match');
    expect(Time::parseAt('+2h'))->toEqual(time() + 7200, '+2h did not match');
    expect(Time::parseAt('+2d'))->toEqual(time() + 172800, '+2d did not match');
    expect(Time::parseAt('+2y'))->toEqual(time() + 63115200, '+2y did not match');
    expect(Time::parseAt('-3m'))->toEqual(time() - 180, '-3m did not match');
    expect(Time::parseAt('-2h'))->toEqual(time() - 7200, '-2h did not match');
    expect(Time::parseAt('-2d'))->toEqual(time() - 172800, '-2d did not match');
    expect(Time::parseAt('-1w'))->toEqual(time() - 604800, '-1w did not match');
    expect(Time::parseAt('-2w'))->toEqual(time() - 1209600, '-2w did not match');
    expect(Time::parseAt('-1mo'))->toEqual(time() - 2592000, '-1mo did not match');
    expect(Time::parseAt('-2mo'))->toEqual(time() - 5184000, '-2mo did not match');
    expect(Time::parseAt('-90s'))->toEqual(time() - 90, '-90s did not match');
    expect(Time::parseAt('-2y'))->toEqual(time() - 63115200, '-2y did not match');
    expect(Time::parseAt('429929439'))->toEqual(429929439);
    expect(Time::parseAt(212334234))->toEqual(212334234);
    expect(Time::parseAt('-43'))->toEqual(time() - 43, '-43 did not match');
    expect(Time::parseAt('invalid'))->toEqual(0);
    expect(Time::parseAt('March 23 1989 UTC'))->toEqual(606614400);
    expect(Time::parseAt('+1 day'))->toEqual(time() + 86400);
});

test('parse input', function (): void {
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

test('date to minutes', function (): void {
    // valid date should diff normally and not throw (dateToRuntime() semantics
    // allow either sign -- negative means the date has already passed -- so this
    // only checks it resolves to a real int rather than asserting a direction)
    expect(Time::dateToMinutes('2020-01-01'))->toBeInt();

    // malformed values reported live from an APC UPS's
    // upsAdvBatteryRecommendedReplaceDate OID (see GH #20555, and the
    // same broken pattern in #19184 and #17390). These used to throw an
    // uncaught Carbon\Exceptions\InvalidFormatException that aborted the
    // entire sensors discovery module for the device; they must now be
    // caught and fall back to 0 instead.
    expect(Time::dateToMinutes('07/30/20:7'))->toBe(0);
    expect(Time::dateToMinutes('06/08/20:3'))->toBe(0);
    expect(Time::dateToMinutes('01/23/20<3'))->toBe(0);
});

test('random time between', function (): void {
    $start = time();
    $end = time() + 3600;
    $randomTime = Time::randomBetween($start, $end)->format('U');
    expect($randomTime)->toBeGreaterThanOrEqual($start);
    expect($randomTime)->toBeLessThanOrEqual($end);

    // test with pesudo random
    $randomTime = Time::pseudoRandomBetween($start, $end, 'U');
    expect($randomTime)->toBeGreaterThanOrEqual($start);
    expect($randomTime)->toBeLessThanOrEqual($end);

    expect(Time::pseudoRandomBetween($start, $end))->toEqual(Time::pseudoRandomBetween($start, $end));
});
