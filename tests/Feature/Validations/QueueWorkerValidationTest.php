<?php

use App\Facades\LibrenmsConfig;
use App\Jobs\DispatchPollingWork;
use App\Models\Device;
use App\Models\PollerCluster;
use App\Queue\FailedJobs;
use App\Queue\QueueStatus;
use App\Queue\RetryTracker;
use App\Queue\WorkedQueues;
use App\Queue\WorkerNode;
use Carbon\Carbon;
use LibreNMS\ValidationResult;
use LibreNMS\Validations\Poller\CheckDispatcherService;
use LibreNMS\Validations\Poller\CheckQueuePolling;
use LibreNMS\Validations\Poller\CheckQueueWorkers;
use Mockery\MockInterface;

uses(LibreNMS\Tests\DBTestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function (): void {
    config(['queue.default' => 'redis']);
    LibrenmsConfig::set('schedule_type.poller', WorkerNode::SCHEDULE_TYPE);
    LibrenmsConfig::set('schedule_type.discovery', WorkerNode::SCHEDULE_TYPE);
});

test('quiet without queue workers', function (): void {
    LibrenmsConfig::set('schedule_type.poller', 'dispatcher');
    LibrenmsConfig::set('schedule_type.discovery', 'dispatcher');
    Device::factory()->create();

    expect((new CheckQueueWorkers)->enabled())->toBeFalse();
    expect((new CheckQueuePolling)->enabled())->toBeFalse();
});

test('inactive worker node', function (): void {
    PollerCluster::factory()->worker()->create(['poller_name' => 'gone.example.com', 'last_report' => now()->subDay()]);
    queueStatus();

    $result = (new CheckQueueWorkers)->validate();

    expect($result->getStatus())->toBe(ValidationResult::FAILURE);
    expect($result->getList())->toBe(['gone.example.com']);
});

test('dispatcher service ignores worker nodes', function (): void {
    PollerCluster::factory()->worker()->create(['last_report' => now()->subDay()]);

    expect((new CheckDispatcherService)->validate()->getStatus())->toBe(ValidationResult::SUCCESS);
});

test('requires aqueue', function (): void {
    config(['queue.default' => 'sync']);

    $result = (new CheckQueueWorkers)->validate();

    expect($result->getStatus())->toBe(ValidationResult::FAILURE);
    $this->assertStringContainsString('sync', $result->getMessage());
});

test('any queue driver', function (): void {
    config(['queue.default' => 'database']);
    Device::factory()->create(['poller_group' => 0]);
    queueStatus();

    expect((new CheckQueueWorkers)->validate()->getStatus())->toBe(ValidationResult::SUCCESS);
});

test('workers not running', function (): void {
    queueStatus(worked: []);

    $result = (new CheckQueueWorkers)->validate();

    expect($result->getStatus())->toBe(ValidationResult::FAILURE);
    expect($result->hasFix())->toBeTrue();
});

test('unworked poller group', function (): void {
    Device::factory()->create(['poller_group' => 2]);
    queueStatus(worked: ['scheduler', 'poll', 'discovery', 'poll-2']);

    $result = (new CheckQueueWorkers)->validate();

    expect($result->getStatus())->toBe(ValidationResult::FAILURE);
    expect($result->getList())->toBe(['discovery-2']);
});

test('queue falling behind', function (): void {
    Device::factory()->create(['poller_group' => 0]);
    queueStatus(ages: [
        'poll' => DispatchPollingWork::pollInterval() + 1,
        'discovery' => 600,
    ]);

    $result = (new CheckQueueWorkers)->validate();

    expect($result->getStatus())->toBe(ValidationResult::WARNING);
    expect($result->getList())->toBe(['poll (' . (DispatchPollingWork::pollInterval() + 1) . 's)']);
});

test('workers ok', function (): void {
    Device::factory()->create(['poller_group' => 0]);
    queueStatus(ages: ['poll' => 5]);

    expect((new CheckQueueWorkers)->validate()->getStatus())->toBe(ValidationResult::SUCCESS);
});

test('queue error', function (): void {
    test()->mock(QueueStatus::class, fn (MockInterface $mock) => $mock->shouldReceive('pollingQueues')->andReturn(['scheduler' => 10]));
    test()->mock(WorkedQueues::class, fn (MockInterface $mock) => $mock->shouldReceive('worked')->andThrow(new RuntimeException('Connection refused')));

    $result = (new CheckQueueWorkers)->validate();

    expect($result->getStatus())->toBe(ValidationResult::FAILURE);
    $this->assertStringContainsString('Connection refused', $result->getMessage());
});

test('not dispatched', function (): void {
    queueStatus(lastDispatched: null);

    $result = (new CheckQueuePolling)->validate();

    expect($result->getStatus())->toBe(ValidationResult::FAILURE);
    expect($result->hasList())->toBeFalse();
});

test('dispatcher failing', function (): void {
    queueStatus(lastDispatched: now()->subMinutes(10), failed: [['finished' => now()->subSeconds(5), 'error' => 'QueryException: Table devices is marked as crashed']]);

    $result = (new CheckQueuePolling)->validate();

    expect($result->getStatus())->toBe(ValidationResult::FAILURE);
    $this->assertStringContainsString('10 minutes ago', $result->getMessage());
    $this->assertStringContainsString('marked as crashed', $result->getList()[0]);
});

test('overdue devices', function (): void {
    queueStatus();
    $overdue = Device::factory()->create(['status' => 1, 'last_polled' => now()->subHour()]);
    Device::factory()->create(['status' => 1, 'last_polled' => now()->subMinute()]);
    Device::factory()->create(['status' => 0, 'last_polled' => now()->subHour()]);
    Device::factory()->create(['status' => 1, 'disabled' => 1, 'last_polled' => now()->subHour()]);

    $result = (new CheckQueuePolling)->validate();

    expect($result->getStatus())->toBe(ValidationResult::WARNING);
    expect($result->getList())->toContain($overdue->hostname);
    expect($result->getList())->toHaveCount(1);
});

test('dispatched and polled', function (): void {
    queueStatus();
    Device::factory()->create(['status' => 1, 'last_polled' => now()->subMinute()]);

    expect((new CheckQueuePolling)->validate()->getStatus())->toBe(ValidationResult::SUCCESS);
});

/**
 * @param  string[]|null  $worked  defaults to the queues for poller group 0
 * @param  array<string, int>  $ages  seconds the oldest job waited, others have none waiting
 * @param  array<int, array{finished: Carbon, error: string}>  $failed  dispatcher runs
 */
function queueStatus(?array $worked = null, array $ages = [], ?Carbon $lastDispatched = new Carbon, array $failed = []): void
{
    test()->mock(WorkedQueues::class, fn (MockInterface $mock) => $mock->shouldReceive('worked')->andReturn($worked ?? ['scheduler', 'poll', 'discovery']));
    test()->mock(FailedJobs::class, fn (MockInterface $mock) => $mock->shouldReceive('list')->with(null, DispatchPollingWork::class, 1)->andReturn(collect($failed)));
    test()->mock(QueueStatus::class, function (MockInterface $mock) use ($ages, $lastDispatched): void {
        $mock->shouldReceive('lastDispatched')->andReturn($lastDispatched);
        $mock->shouldReceive('pollingQueues')->andReturnUsing(fn () => new QueueStatus(new RetryTracker, new WorkedQueues)->pollingQueues());
        $mock->shouldReceive('overdueDevices')->andReturnUsing(fn () => new QueueStatus(new RetryTracker, new WorkedQueues)->overdueDevices());
        $mock->shouldReceive('ages')->andReturnUsing(fn (array $names): array => array_intersect_key($ages, array_flip($names)) + array_fill_keys($names, 0));
    });
}