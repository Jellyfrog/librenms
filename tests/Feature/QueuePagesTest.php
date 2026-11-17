<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use App\Horizon\JobMonitor;
use App\Models\Device;
use App\Models\PollerCluster;
use App\Models\User;
use App\Queue\DeviceLocks;
use App\Queue\FailedJobs;
use App\Queue\QueueStatus;
use App\Queue\RetryTracker;
use App\Queue\WorkerNode;
use Carbon\Carbon;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    config(['queue.default' => 'redis']);
    LibrenmsConfig::set('schedule_type.poller', WorkerNode::SCHEDULE_TYPE);
});

test('queues tab shows queues workers and failed jobs', function (): void {
    $device = Device::factory()->create();
    $this->mock(RetryTracker::class, fn (MockInterface $mock) => $mock->shouldReceive('retrying')->andReturn(collect([
        ['device_id' => $device->device_id, 'name' => 'PollDevice', 'queue' => 'poll-7', 'attempt' => 2, 'tries' => 4, 'retry_at' => Carbon::now()->addMinute()],
    ])));
    $this->mock(FailedJobs::class, function (MockInterface $mock) use ($device): void {
        $mock->shouldReceive('list')->andReturn(collect([failedQueueJob(['device_id' => $device->device_id])]));
        $mock->shouldReceive('count')->andReturn(1);
    });
    $overdue = Device::factory()->create(['hostname' => 'overdue.example.com', 'last_polled' => now()->subHour()]);
    $this->mock(DeviceLocks::class, fn (MockInterface $mock) => $mock->shouldReceive('held')->andReturn(true));
    $this->mock(QueueStatus::class, function (MockInterface $mock) use ($overdue): void {
        $mock->shouldReceive('overdueDevices')->andReturn(new \Illuminate\Database\Eloquent\Collection([$overdue]));
        $mock->shouldReceive('queues')->andReturn(collect([
            ['name' => 'poll-7', 'pending' => 3, 'scheduled' => 40, 'retrying' => 1, 'reserved' => 2, 'worked' => true, 'age' => 12, 'interval' => 297, 'busy' => 4.5, 'workers' => 6],
        ]));
        $mock->shouldReceive('workers')->andReturn(collect([
            ['name' => 'node-a', 'status' => 'running', 'last_report' => Carbon::now(), 'queues' => ['poll-7', 'discovery-7']],
        ]));
    });

    $this->actingAs(User::factory()->admin()->create(['enabled' => true]))
        ->get(route('poller.queues'))
        ->assertOk()
        ->assertSee(__('Refresh'))
        ->assertSee('poll-7')
        ->assertSee('node-a')
        ->assertSee('poll-7, discovery-7')
        ->assertSee('4.5 / 6')
        ->assertSee(url(config('horizon.path')))
        ->assertSee('PollDevice')
        ->assertSee('SNMP timeout')
        ->assertSeeInOrder([__('Down Devices Retrying'), 'PollDevice', '2 / 4', __('Failed Jobs')])
        ->assertSee(__('A device being down is not a failed job.'))
        ->assertSee('aria-controls="failed-jobs-help"', false)
        ->assertSee(route('poller.queues.retry', 'job-uuid'))
        ->assertSeeInOrder([__('Overdue Devices'), 'overdue.example.com', 'held'])
        ->assertSee(route('poller.queues.release-lock', $overdue->device_id));
});

test('release device lock', function (): void {
    $device = Device::factory()->create();
    $this->mock(DeviceLocks::class, fn (MockInterface $mock) => $mock->shouldReceive('release')->once()->withArgs(fn (Device $released): bool => $released->is($device)));

    $this->actingAs(User::factory()->read()->create(['enabled' => true]))
        ->delete(route('poller.queues.release-lock', $device->device_id))
        ->assertForbidden();
    $this->actingAs(userWithPermissions('worker.update'))
        ->delete(route('poller.queues.release-lock', $device->device_id))
        ->assertRedirect();
});

test('queues require permission', function (): void {
    $user = User::factory()->create(['enabled' => true]);

    $this->actingAs($user)->get(route('poller.queues'))->assertForbidden();
    $this->actingAs($user)->post(route('poller.queues.retry', 'job-uuid'))->assertForbidden();
    $this->actingAs($user)->delete(route('poller.queues.forget', 'job-uuid'))->assertForbidden();
});

test('view permission cannot manage failed jobs', function (): void {
    mockQueuePage();
    $user = User::factory()->read()->create(['enabled' => true]);

    $this->actingAs($user)->get(route('poller.queues'))
        ->assertOk()
        ->assertDontSee(route('poller.queues.retry', 'job-uuid'));
    $this->actingAs($user)->post(route('poller.queues.retry', 'job-uuid'))->assertForbidden();
    $this->actingAs($user)->delete(route('poller.queues.forget', 'job-uuid'))->assertForbidden();
});

test('horizon permission does not grant queue pages', function (): void {
    $user = userWithPermissions('worker.horizon');

    $this->actingAs($user)->get(route('poller.queues'))->assertForbidden();
    $this->actingAs($user)->post(route('poller.queues.retry', 'job-uuid'))->assertForbidden();
});

test('queues require a queue', function (): void {
    config(['queue.default' => 'sync']);

    $this->actingAs(User::factory()->admin()->create(['enabled' => true]))->get(route('poller.queues'))->assertNotFound();
});

test('queues without horizon', function (): void {
    config(['queue.default' => 'database']);
    mockQueuePage();

    $this->actingAs(User::factory()->admin()->create(['enabled' => true]))
        ->get(route('poller.queues'))
        ->assertOk()
        ->assertSee(__('Failed Jobs'))
        ->assertDontSee(url(config('horizon.path')));
});

test('queues hidden when queue workers are not used', function (): void {
    LibrenmsConfig::set('schedule_type.poller', 'dispatcher');
    LibrenmsConfig::set('schedule_type.discovery', 'dispatcher');
    $user = User::factory()->admin()->create(['enabled' => true]);

    $this->actingAs($user)->get(route('poller.queues'))->assertNotFound();
    $this->actingAs($user)->get(route('poller.performance'))
        ->assertOk()
        ->assertDontSee(route('poller.queues'))
        ->assertDontSee(url(config('horizon.path')));
});

test('pollers page lists worker nodes without stats', function (): void {
    PollerCluster::factory()->worker()->create(['poller_name' => 'worker-node.example.com', 'last_report' => now()]);

    $this->actingAs(User::factory()->admin()->create(['enabled' => true]))
        ->get(route('poller.index'))
        ->assertOk()
        ->assertSee('worker-node.example.com')
        ->assertSee(route('poller.queues'));
});

test('retry and forget failed job', function (): void {
    $this->mock(FailedJobs::class, function (MockInterface $mock): void {
        $mock->shouldReceive('retry')->once()->with('job-uuid');
        $mock->shouldReceive('forget')->once()->with('job-uuid');
    });
    $user = userWithPermissions('worker.update');

    $this->actingAs($user)->post(route('poller.queues.retry', 'job-uuid'))->assertRedirect();
    $this->actingAs($user)->delete(route('poller.queues.forget', 'job-uuid'))->assertRedirect();
});

test('capture jobs offers recording when not monitoring', function (): void {
    $device = Device::factory()->create();
    $this->mock(JobMonitor::class, function (MockInterface $mock) use ($device): void {
        $mock->shouldReceive('horizonRunning')->andReturn(true);
        $mock->shouldReceive('isMonitoring')->with("device:$device->device_id")->andReturn(false);
        $mock->shouldNotReceive('monitoredJobs');
    });
    $this->mock(FailedJobs::class, fn (MockInterface $mock) => $mock->shouldReceive('list')->with($device->device_id)->andReturn(collect()));

    $this->actingAs(User::factory()->admin()->create(['enabled' => true]))
        ->get(route('device', ['device' => $device->device_id, 'tab' => 'capture', 'section' => 'jobs']))
        ->assertOk()
        ->assertSee(route('device.jobs.monitor', $device))
        ->assertSee("activeTab: 'jobs'", false)
        ->assertSee('No failed jobs');
});

test('capture jobs shows recorded jobs', function (): void {
    $device = Device::factory()->create();
    $this->mock(JobMonitor::class, function (MockInterface $mock) use ($device): void {
        $mock->shouldReceive('horizonRunning')->andReturn(true);
        $mock->shouldReceive('isMonitoring')->andReturn(true);
        $mock->shouldReceive('monitoredJobs')->with("device:$device->device_id")->andReturn(collect([
            failedQueueJob(['id' => 'completed-uuid', 'name' => 'DiscoverDevice:' . $device->device_id, 'status' => 'completed', 'error' => '', 'exception' => '']),
        ]));
    });
    $this->mock(FailedJobs::class, fn (MockInterface $mock) => $mock->shouldReceive('list')->andReturn(collect()));

    $this->actingAs(User::factory()->admin()->create(['enabled' => true]))
        ->get(route('device', ['device' => $device->device_id, 'tab' => 'capture', 'section' => 'jobs']))
        ->assertOk()
        ->assertSee('DiscoverDevice:' . $device->device_id)
        ->assertSee(route('device.jobs.stop-monitoring', $device));
});

test('capture shows failed jobs without horizon', function (): void {
    config(['queue.default' => 'database']);
    $device = Device::factory()->create();
    $this->mock(FailedJobs::class, fn (MockInterface $mock) => $mock->shouldReceive('list')->andReturn(collect([failedQueueJob(['device_id' => $device->device_id])])));

    $this->actingAs(User::factory()->admin()->create(['enabled' => true]))
        ->get(route('device', ['device' => $device->device_id, 'tab' => 'capture', 'section' => 'jobs']))
        ->assertOk()
        ->assertSee('SNMP timeout')
        ->assertDontSee(route('device.jobs.monitor', $device));
    $this->actingAs(User::factory()->admin()->create(['enabled' => true]))
        ->post(route('device.jobs.monitor', $device))
        ->assertNotFound();
});

test('capture jobs hidden without a queue', function (): void {
    config(['queue.default' => 'sync']);
    $device = Device::factory()->create();
    $this->mock(FailedJobs::class, fn (MockInterface $mock) => $mock->shouldNotReceive('list'));

    $this->actingAs(User::factory()->admin()->create(['enabled' => true]))
        ->get(route('device', ['device' => $device->device_id, 'tab' => 'capture']))
        ->assertOk()
        ->assertDontSee(route('device.jobs.monitor', $device));
});

test('recording requires update permission', function (): void {
    $device = Device::factory()->create();

    $this->actingAs(User::factory()->read()->create(['enabled' => true]))
        ->post(route('device.jobs.monitor', $device))
        ->assertForbidden();
    $this->actingAs(userWithPermissions('worker.horizon'))
        ->delete(route('device.jobs.stop-monitoring', $device))
        ->assertForbidden();
});

test('recording requires access to the device', function (): void {
    $device = Device::factory()->create();
    $this->mock(JobMonitor::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('monitor');
        $mock->shouldNotReceive('stopMonitoring');
    });
    $user = userWithPermissions('worker.update');

    $this->actingAs($user)->post(route('device.jobs.monitor', $device))->assertForbidden();
    $this->actingAs($user)->delete(route('device.jobs.stop-monitoring', $device))->assertForbidden();
});

test('start and stop recording device jobs', function (): void {
    $device = Device::factory()->create();
    $this->mock(JobMonitor::class, function (MockInterface $mock) use ($device): void {
        $mock->shouldReceive('horizonRunning')->andReturn(true);
        $mock->shouldReceive('monitor')->once()->with("device:$device->device_id");
        $mock->shouldReceive('stopMonitoring')->once()->with("device:$device->device_id");
    });
    $user = userWithPermissions('worker.update', 'device.viewAll');

    $capture = route('device', ['device' => $device->device_id, 'tab' => 'capture', 'section' => 'jobs']);
    $this->actingAs($user)->post(route('device.jobs.monitor', $device))->assertRedirect($capture);
    $this->actingAs($user)->delete(route('device.jobs.stop-monitoring', $device))->assertRedirect($capture);
});

function userWithPermissions(string ...$permissions): User
{
    $user = User::factory()->create(['enabled' => true]);
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }
    $user->givePermissionTo($permissions);

    return $user;
}

function mockQueuePage(): void
{
    test()->mock(RetryTracker::class, fn (MockInterface $mock) => $mock->shouldReceive('retrying')->andReturn(collect()));
    test()->mock(QueueStatus::class, function (MockInterface $mock): void {
        $mock->shouldReceive('queues')->andReturn(collect());
        $mock->shouldReceive('workers')->andReturn(collect());
        $mock->shouldReceive('overdueDevices')->andReturn(new \Illuminate\Database\Eloquent\Collection);
    });
    test()->mock(FailedJobs::class, function (MockInterface $mock): void {
        $mock->shouldReceive('list')->andReturn(collect([failedQueueJob()]));
        $mock->shouldReceive('count')->andReturn(1);
    });
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function failedQueueJob(array $overrides = []): array
{
    $device_id = $overrides['device_id'] ?? 1;

    return array_merge([
        'id' => 'job-uuid',
        'name' => 'PollDevice',
        'queue' => 'poll',
        'status' => 'failed',
        'device_id' => $device_id,
        'attempts' => 4,
        'started' => Carbon::now()->subSeconds(5),
        'finished' => Carbon::now(),
        'runtime' => 5.0,
        'error' => 'SNMP timeout',
        'exception' => "SNMP timeout\n#0 stack",
        'retried' => false,
    ], $overrides);
}