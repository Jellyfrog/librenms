<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\Eventlog;
use Carbon\Carbon;
use LibreNMS\Data\Store\Rrd;
use LibreNMS\Exceptions\RrdCachedConnectionException;
use LibreNMS\RRD\RrdProcess;

beforeEach(function (): void {
    $this->runs = 0;
    $this->failing = true;
    $this->events = [];

    Carbon::setTestNow('2026-01-01 00:00:00');
    LibrenmsConfig::set('rrdtool_version', '1.6');
    LibrenmsConfig::set('rrd_dir', '/tmp');

    $process = Mockery::mock(RrdProcess::class);
    $process->shouldReceive('run')->andReturnUsing(function () {
        $this->runs++;
        if ($this->failing) {
            throw new RrdCachedConnectionException('connection refused');
        }

        return '';
    });
    $process->shouldReceive('stop');
    $this->app->bind(RrdProcess::class, fn () => $process);

    $eventlog = Mockery::mock(Eventlog::class);
    $eventlog->shouldReceive('_log')->andReturnUsing(function (string $text): void {
        $this->events[] = $text;
    });
    $this->app->instance(Eventlog::class, $eventlog);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

test('backs off and recovers', function (): void {
    $rrd = new Rrd();

    // trips after threshold
    writeTimes($rrd, 3);
    expect($this->runs)->toBe(3);
    expect($this->events)->toHaveCount(1);
    $this->assertStringContainsString('suspended for 30s', $this->events[0]);

    // suspended: no rrdtool calls
    writeTimes($rrd, 5);
    expect($this->runs)->toBe(3);

    // half-open: one retry, fails, re-trips with doubled backoff
    Carbon::setTestNow(Carbon::now()->addSeconds(30));
    writeTimes($rrd, 3);
    expect($this->runs)->toBe(4);
    $this->assertStringContainsString('suspended for 60s', $this->events[1]);

    Carbon::setTestNow(Carbon::now()->addSeconds(59));
    writeTimes($rrd, 1);
    expect($this->runs)->toBe(4);

    // recovers once rrdtool works again
    $this->failing = false;
    Carbon::setTestNow(Carbon::now()->addSeconds(1));
    writeTimes($rrd, 3);
    expect($this->runs)->toBe(7);
    expect($this->events[2])->toBe('RRD updates resumed');

    // breaker fully reset: needs threshold failures to trip again at base backoff
    $this->failing = true;
    writeTimes($rrd, 2);
    expect($this->events)->toHaveCount(3);
    writeTimes($rrd, 1);
    $this->assertStringContainsString('suspended for 30s', $this->events[3]);
});

test('backoff is capped', function (): void {
    $rrd = new Rrd();
    writeTimes($rrd, 3);

    for ($i = 0; $i < 10; $i++) {
        Carbon::setTestNow(Carbon::now()->addSeconds(600));
        writeTimes($rrd, 1);
    }

    $this->assertStringContainsString('suspended for 600s', end($this->events));
});

function writeTimes(Rrd $rrd, int $count): void
{
    $device = new Device(['hostname' => 'test']);
    for ($i = 0; $i < $count; $i++) {
        $rrd->write('test', ['a' => 1], [], ['device' => $device]);
    }
}