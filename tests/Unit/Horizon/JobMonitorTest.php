<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Horizon\JobMonitor;
use Illuminate\Support\Collection;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\TagRepository;

test('released jobs are shown as retrying', function () {
    $tags = Mockery::mock(TagRepository::class);
    $tags->shouldReceive('paginate')->with('device:9', 0, 50)->andReturn(['a', 'b', 'c']);
    $jobs = Mockery::mock(JobRepository::class);
    $jobs->shouldReceive('getJobs')->with(['a', 'b', 'c'])->andReturn(new Collection([
        job('a', 'pending', 0),
        job('b', 'pending', 1),
        job('c', 'completed', 4),
    ]));

    $monitored = new JobMonitor($jobs, $tags, Mockery::mock(MasterSupervisorRepository::class))->monitoredJobs('device:9');

    expect($monitored->pluck('status', 'id')->all())->toBe(['a' => 'pending', 'b' => 'retrying', 'c' => 'completed']);
    expect($monitored[0]['name'])->toBe('PollDevice');
    expect($monitored[0]['device_id'])->toBe(9);
});

test('only available when horizon is running', function () {
    config(['queue.default' => 'redis']);
    $masters = Mockery::mock(MasterSupervisorRepository::class);
    $masters->shouldReceive('names')->andReturn([], ['node1-AbCd']);
    $monitor = new JobMonitor(Mockery::mock(JobRepository::class), Mockery::mock(TagRepository::class), $masters);

    expect($monitor->horizonRunning())->toBeFalse('other queue workers do not record jobs');
    expect($monitor->horizonRunning())->toBeTrue();

    config(['queue.default' => 'database']);
    expect($monitor->horizonRunning())->toBeFalse();
});

function job(string $id, string $status, int $attempts): \stdClass
{
    return (object) [
        'id' => $id,
        'name' => \App\Jobs\PollDevice::class,
        'queue' => 'poll',
        'status' => $status,
        'payload' => json_encode(['attempts' => $attempts, 'device_id' => 9, 'tags' => ['poll', 'device:9']]),
        'exception' => null,
        'reserved_at' => null,
        'completed_at' => null,
        'failed_at' => null,
        'retried_by' => null,
    ];
}
