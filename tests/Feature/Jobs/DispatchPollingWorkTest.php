<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Jobs\DiscoverDevice;
use App\Jobs\DispatchPollingWork;
use App\Jobs\PollDevice;
use App\Models\Device;
use App\Queue\WorkedQueues;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('discovery dispatch', function (int $status, ?int $discovered, bool $polled, bool $expected) {
    Queue::fake();
    $device = Device::factory()->create([
        'status' => $status,
        'last_discovered' => $discovered === null ? null : now()->subMinutes($discovered),
        'last_discovered_timetaken' => $discovered === null ? null : 0,
        'last_polled' => $polled ? now()->subMinute() : null,
    ]);

    new DispatchPollingWork(poll: false, discover: true)->handle();

    if ($expected) {
        Queue::assertPushed(DiscoverDevice::class, fn (DiscoverDevice $job) => $job->device_id == $device->device_id);
    } else {
        Queue::assertNotPushed(DiscoverDevice::class, fn (DiscoverDevice $job) => $job->device_id == $device->device_id);
    }
})->with('discoveryProvider');

/**
 * @return array<string, array{int, int|null, bool, bool}>
 */
dataset('discoveryProvider', function () {
    return [
        'new' => [1, null, false, true],
        'down, new' => [0, null, false, true],
        'rediscover requested' => [1, null, true, true],
        'down, rediscover requested' => [0, null, true, false],
        'due' => [1, 420, true, true],
        'not due' => [1, 60, true, false],
        'down, due' => [0, 420, true, false],
    ];
});

test('queued for the poller group', function () {
    Queue::fake();
    $default = Device::factory()->create(['status' => 1, 'poller_group' => 0, 'last_discovered' => null, 'last_polled' => now()->subMinutes(10)]);
    $grouped = Device::factory()->create(['status' => 1, 'poller_group' => 3, 'last_discovered' => null, 'last_polled' => now()->subMinutes(10)]);

    new DispatchPollingWork(poll: true, discover: true)->handle();

    Queue::assertPushedOn('poll', PollDevice::class, fn (PollDevice $job) => $job->device_id == $default->device_id);
    Queue::assertPushedOn('discovery', DiscoverDevice::class, fn (DiscoverDevice $job) => $job->device_id == $default->device_id);
    Queue::assertPushedOn('poll-3', PollDevice::class, fn (PollDevice $job) => $job->device_id == $grouped->device_id);
    Queue::assertPushedOn('discovery-3', DiscoverDevice::class, fn (DiscoverDevice $job) => $job->device_id == $grouped->device_id);
});

test('unworked poller group is not queued', function () {
    Queue::fake();
    workedQueues(['poll', 'discovery', 'poll-1']);
    $worked = Device::factory()->create(['status' => 1, 'poller_group' => 1, 'last_discovered' => now()->subMinute(), 'last_polled' => now()->subMinutes(10)]);
    $unworked = Device::factory()->create(['status' => 1, 'poller_group' => 2, 'last_discovered' => now()->subMinute(), 'last_polled' => now()->subMinutes(10)]);

    new DispatchPollingWork(poll: true, discover: false)->handle();

    Queue::assertPushedOn('poll-1', PollDevice::class, fn (PollDevice $job) => $job->device_id == $worked->device_id);
    Queue::assertNotPushed(PollDevice::class, fn (PollDevice $job) => $job->device_id == $unworked->device_id);
});

test('everything is queued when no worker has reported', function () {
    Queue::fake();
    workedQueues([]);
    $device = Device::factory()->create(['status' => 1, 'poller_group' => 2, 'last_discovered' => now()->subMinute(), 'last_polled' => now()->subMinutes(10)]);

    new DispatchPollingWork(poll: true, discover: false)->handle();

    Queue::assertPushedOn('poll-2', PollDevice::class, fn (PollDevice $job) => $job->device_id == $device->device_id);
});

test('scheduled on scheduler queue', function () {
    expect((new DispatchPollingWork)->queue)->toBe('scheduler');
});

test('discovery due before the next dispatch is delayed', function () {
    Queue::fake();
    $device = Device::factory()->create([
        'status' => 1,
        'last_discovered' => now()->subSeconds(21600 - 8),
        'last_discovered_timetaken' => 0,
    ]);

    new DispatchPollingWork(poll: false, discover: true)->handle();

    // due 3 seconds early, at 21597 seconds
    Queue::assertPushed(DiscoverDevice::class, fn (DiscoverDevice $job) => $job->device_id == $device->device_id && $job->delay >= 4 && $job->delay <= 6);
});

test('poll dispatch', function (int $status, bool $discovered, ?int $polled, bool $expected) {
    Queue::fake();
    $device = Device::factory()->create([
        'status' => $status,
        'last_discovered' => $discovered ? now()->subMinute() : null,
        'last_polled' => $polled === null ? null : now()->subMinutes($polled),
        'last_polled_timetaken' => $polled === null ? null : 0,
    ]);

    new DispatchPollingWork(poll: true, discover: false)->handle();

    if ($expected) {
        Queue::assertPushed(PollDevice::class, fn (PollDevice $job) => $job->device_id == $device->device_id);
    } else {
        Queue::assertNotPushed(PollDevice::class, fn (PollDevice $job) => $job->device_id == $device->device_id);
    }
})->with('pollProvider');

/**
 * @return array<string, array{int, bool, int|null, bool}>
 */
dataset('pollProvider', function () {
    return [
        'new' => [1, false, null, false],
        'down, new' => [0, false, null, false],
        'discovered, never polled' => [1, true, null, true],
        'due' => [1, true, 10, true],
        'down, due' => [0, true, 10, true],
        'not due' => [1, true, 1, false],
        'rediscover requested, due' => [1, false, 10, true],
        'down, rediscover requested, due' => [0, false, 10, true],
    ];
});

test('poll due before the next dispatch is delayed', function () {
    Queue::fake();
    $device = Device::factory()->create([
        'status' => 1,
        'last_discovered' => now()->subMinute(),
        'last_polled' => now()->subSeconds(300 - 8),
        'last_polled_timetaken' => 0,
    ]);

    new DispatchPollingWork(poll: true, discover: false)->handle();

    // due 3 seconds early, at 297 seconds
    Queue::assertPushed(PollDevice::class, fn (PollDevice $job) => $job->device_id == $device->device_id && $job->delay >= 4 && $job->delay <= 6);
});

/**
 * @param  string[]  $queues
 */
function workedQueues(array $queues): void
{
    $this->mock(WorkedQueues::class, fn ($mock) => $mock->shouldReceive('worked')->andReturn($queues));
}