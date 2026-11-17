<?php

use App\Facades\LibrenmsConfig;
use App\Jobs\DispatchPollingWork;
use App\Jobs\PollDevice;
use App\Models\Device;
use App\Models\FailedJob;
use App\Models\PollerCluster;
use App\Queue\DeviceLocks;
use App\Queue\FailedJobs;
use App\Queue\QueueStatus;
use App\Queue\RetryTracker;
use App\Queue\WorkedQueues;
use App\Queue\WorkerNode;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\RedisQueue;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use LibreNMS\Util\ModuleList;

uses(LibreNMS\Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    LibrenmsConfig::set('schedule_type.poller', WorkerNode::SCHEDULE_TYPE);
});

test('released jobs are recorded until they run again', function (): void {
    $this->freezeSecond();
    Device::factory()->create(['device_id' => 9, 'status' => 0]);
    $status = new RetryTracker;
    $job = job('job-9', attempts: 2);

    $status->jobReleased($job, 120);

    $retrying = $status->retrying();
    expect($retrying)->toHaveCount(1);
    expect($retrying[0]['device_id'])->toBe(9);
    expect($retrying[0]['name'])->toBe('PollDevice');
    expect($retrying[0]['queue'])->toBe('poll-1');
    expect($retrying[0]['attempt'])->toBe(2);
    expect($retrying[0]['tries'])->toBe(4);
    expect($retrying[0]['retry_at'])->toEqual(now()->addMinutes(2));

    $status->jobStarted(job('other', attempts: 1));
    expect($status->retrying())->toHaveCount(1, 'a first attempt is not a retry');
    $status->jobReleased(job('job-10', attempts: 1, device_id: 10), 60);
    expect($status->retrying())->toHaveCount(1, 'only down devices are listed');

    $status->jobStarted($job);
    expect($status->retrying())->toHaveCount(0);
});

test('stale retries are dropped', function (): void {
    $this->freezeSecond();
    Device::factory()->create(['device_id' => 9, 'status' => 0]);
    $status = new RetryTracker;
    $status->jobReleased(job('job-3', attempts: 1), 60);

    $this->travel(2)->hours();

    expect($status->retrying())->toHaveCount(0, 'its job was removed from the queue');
});

test('queue sizes', function (): void {
    Device::factory()->create(['device_id' => 9, 'status' => 0, 'poller_group' => 0]);
    Device::factory()->create(['device_id' => 1, 'status' => 0, 'poller_group' => 0]);
    $queue = Mockery::mock(RedisQueue::class);
    $queue->shouldReceive('pendingSize')->andReturn(1);
    $queue->shouldReceive('delayedSize')->andReturn(2);
    $queue->shouldReceive('reservedSize')->andReturn(0);
    $queue->shouldReceive('creationTimeOfOldestPendingJob')->andReturnUsing(fn (string $name) => $name == 'poll' ? time() - 30 : null);
    Queue::shouldReceive('connection')->withNoArgs()->andReturn($queue);
    Device::factory()->create(['poller_group' => 0, 'last_polled_timetaken' => 150, 'last_discovered_timetaken' => 21600]);
    app(WorkedQueues::class)->looping('poll');
    PollerCluster::factory()->worker()->create(['poller_groups' => '0', 'poller_workers' => 5, 'last_report' => now()]);
    PollerCluster::factory()->worker()->create(['poller_groups' => '0', 'poller_workers' => 3, 'last_report' => now()]);
    PollerCluster::factory()->worker()->create(['poller_groups' => '0', 'poller_workers' => 9, 'last_report' => now()->subDay()]);
    // down
    app(RetryTracker::class)->jobReleased(job('job-9', attempts: 2, queue: 'poll'), 60);
    app(RetryTracker::class)->jobReleased(job('job-1', attempts: 2, queue: 'poll', device_id: 1), -10);

    // due, it left the delayed jobs
    $queues = app(QueueStatus::class)->queues()->keyBy('name');

    expect($queues->keys()->all())->toBe(['default', 'poll', 'scheduler']);
    expect($queues['poll'])->toEqual([
        'name' => 'poll',
        'pending' => 1,
        'scheduled' => 1,
        'retrying' => 1,
        'reserved' => 0,
        'worked' => true,
        'age' => 30,
        'interval' => DispatchPollingWork::pollInterval(),
        'busy' => round(150 / DispatchPollingWork::pollFrequency(), 1),
        'workers' => 8,
    ]);
    expect($queues['scheduler']['worked'])->toBeFalse();
    expect($queues['scheduler']['age'])->toBe(0);
});

test('workers are shown with the node name', function (): void {
    PollerCluster::factory()->worker()->create(['node_id' => 'node-a', 'poller_name' => 'poller1.example.com', 'last_report' => now()]);
    PollerCluster::factory()->worker()->create(['node_id' => 'node-b', 'poller_name' => '', 'last_report' => now()->subDay()]);
    PollerCluster::factory()->create(['poller_name' => 'dispatcher-service']);

    $workers = app(QueueStatus::class)->workers();

    expect($workers->pluck('name')->all())->toBe(['node-b', 'poller1.example.com']);
    expect($workers->pluck('status')->all())->toBe(['inactive', 'running']);
    expect($workers[1]['queues'])->toBe(['poll', 'discovery'], 'from the node settings');
});

test('device lock held until released', function (): void {
    $device = Device::factory()->create(['poller_group' => 2]);
    $locks = app(DeviceLocks::class);
    expect($locks->held($device))->toBeFalse();
    expect($locks->held($device))->toBeFalse('checking does not take the lock');

    // DispatchPollingWork queues the device
    expect(new \Illuminate\Bus\UniqueLock(app(\Illuminate\Contracts\Cache\Repository::class))->acquire(new PollDevice($device->device_id, ModuleList::fromUserOverrides([]))->onQueue('poll-2')))->toBeTrue();
    expect($locks->held($device))->toBeTrue();

    $locks->release($device);
    expect($locks->held($device))->toBeFalse();
});

test('overdue devices', function (): void {
    $overdue = Device::factory()->create(['status' => 1, 'last_polled' => now()->subHour()]);
    Device::factory()->create(['status' => 1, 'last_polled' => now()->subMinute()]);
    Device::factory()->create(['status' => 0, 'last_polled' => now()->subHour()]);
    Device::factory()->create(['status' => 1, 'disabled' => 1, 'last_polled' => now()->subHour()]);

    expect(app(QueueStatus::class)->overdueDevices()->pluck('device_id')->all())->toBe([$overdue->device_id]);
});

test('last dispatched', function (): void {
    $this->freezeSecond();
    $status = app(QueueStatus::class);
    expect($status->lastDispatched())->toBeNull();

    $status->recordDispatched();

    expect($status->lastDispatched())->toEqual(now());
});

test('device jobs are queued with their device', function (): void {
    // every queue driver builds the payload the same way
    $created = null;
    Queue::createPayloadUsing(function ($connection, $queue, array $payload) use (&$created): array {
        $created = $payload;

        return [];
    });
    Queue::connection('database')->push(new PollDevice(42, ModuleList::fromUserOverrides([])), queue: 'poll');

    expect($created['device_id'] ?? null)->toBe(42);
});

test('failed jobs by device and class', function (): void {
    failedJob(PollDevice::class, ['device_id' => 9], "RuntimeException: snmp crashed in /opt/librenms/app/Jobs/PollDevice.php:146\nStack trace:\n#0 {main}");
    failedJob(PollDevice::class, ['device_id' => 10]);
    $dispatch = failedJob(DispatchPollingWork::class);
    $failed = new FailedJobs;

    expect($failed->count())->toBe(3);

    $device = $failed->list(device_id: 9);
    expect($device)->toHaveCount(1);
    expect($device[0]['name'])->toBe('PollDevice');
    expect($device[0]['device_id'])->toBe(9);
    expect($device[0]['status'])->toBe('failed');
    expect($device[0]['error'])->toBe('RuntimeException: snmp crashed');

    expect($failed->list(job: DispatchPollingWork::class)->pluck('id')->all())->toBe([$dispatch->uuid]);
});

test('forget failed job', function (): void {
    $job = failedJob(PollDevice::class);

    (new FailedJobs)->forget($job->uuid);

    expect(FailedJob::count())->toBe(0);
});

test('exception summary omits location and trace', function (string $exception, string $expected): void {
    expect(FailedJobs::exceptionSummary($exception))->toBe($expected);
})->with('exceptionProvider');

/**
 * @return array<string, array{string, string}>
 */
dataset('exceptionProvider', function () {
    $trace = "\nStack trace:\n#0 /opt/librenms/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(123): handle()\n#1 {main}";

    return [
        'file' => [
            'App\\Exceptions\\PollingFailedException: Failed to poll device 6899: snmp down in /opt/librenms/app/Jobs/PollDevice.php:146' . $trace,
            'App\\Exceptions\\PollingFailedException: Failed to poll device 6899: snmp down',
        ],
        'message mentions a file' => [
            'TypeError: Dns::getAddresses(): Argument #1 ($hostname) must be of type string, null given, called in /opt/librenms/LibreNMS/Util/Dns.php on line 51 and defined in /opt/librenms/LibreNMS/Util/Dns.php:69' . $trace,
            'TypeError: Dns::getAddresses(): Argument #1 ($hostname) must be of type string, null given, called in /opt/librenms/LibreNMS/Util/Dns.php on line 51 and defined',
        ],
        'closure' => [
            "RuntimeException: Simulated crash in laravel-serializable-closure://function (): void {\n    throw new RuntimeException('Simulated crash');\n}:2" . $trace,
            'RuntimeException: Simulated crash',
        ],
        'multi-line message' => [
            "ErrorException: first line\nsecond line in /opt/librenms/includes/polling/ports.inc.php:798" . $trace,
            'ErrorException: first line second line',
        ],
        'no location' => [
            'Illuminate\\Queue\\TimeoutExceededException: PollDevice:1 has timed out.',
            'Illuminate\\Queue\\TimeoutExceededException: PollDevice:1 has timed out.',
        ],
    ];
});

function job(string $uuid, int $attempts, string $queue = 'poll-1', int $device_id = 9): Job
{
    $job = Mockery::mock(Job::class);
    $job->shouldReceive('uuid')->andReturn($uuid);
    $job->shouldReceive('payload')->andReturn(['displayName' => PollDevice::class, 'device_id' => $device_id]);
    $job->shouldReceive('getQueue')->andReturn($queue);
    $job->shouldReceive('attempts')->andReturn($attempts);
    $job->shouldReceive('maxTries')->andReturn(4);

    return $job;
}

/**
 * @param  array<string, mixed>  $payload
 */
function failedJob(string $class, array $payload = [], string $exception = 'RuntimeException: failed'): FailedJob
{
    $job = new FailedJob;
    $job->forceFill([
        'uuid' => (string) Str::uuid(),
        'connection' => 'redis',
        'queue' => 'poll',
        'payload' => ['displayName' => $class, 'attempts' => 1] + $payload,
        'exception' => $exception,
    ])->save();

    return $job;
}