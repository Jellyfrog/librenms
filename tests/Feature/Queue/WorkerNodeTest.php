<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use App\Jobs\DiscoverDevice;
use App\Jobs\PollDevice;
use App\Models\PollerCluster;
use App\Models\User;
use App\Queue\PollingQueues;
use App\Queue\WorkedQueues;
use App\Queue\WorkerNode;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('queue names match dispatcher', function (string $job, array $groups, array $expected) {
    expect(PollingQueues::forGroups($groups, poll: $job == PollDevice::class, discover: $job == DiscoverDevice::class))->toBe($expected);
})->with('queueNameProvider');

/**
 * @return array<string, array{class-string, int[], string[]}>
 */
dataset('queueNameProvider', function () {
    return [
        'default group' => [PollDevice::class, [0], ['poll']],
        'single group' => [DiscoverDevice::class, [3], ['discovery-3']],
        'multiple groups' => [PollDevice::class, [0, 1, 2], ['poll', 'poll-1', 'poll-2']],
        'duplicates' => [PollDevice::class, [1, 1], ['poll-1']],
    ];
});

test('node without settings uses global settings', function () {
    LibrenmsConfig::set('distributed_poller_group', '0,2');
    LibrenmsConfig::set('service_poller_workers', 8);
    LibrenmsConfig::set('service_discovery_workers', 4);

    expect(WorkerNode::queues(PollerCluster::factory()->worker()->make(['poller_groups' => ''])))->toBe([
        'poller' => ['queues' => ['poll', 'poll-2'], 'workers' => 8],
        'discovery' => ['queues' => ['discovery', 'discovery-2'], 'workers' => 4],
    ]);
});

test('node settings override global settings', function () {
    LibrenmsConfig::set('distributed_poller_group', '0');
    LibrenmsConfig::set('service_poller_workers', 8);
    $node = PollerCluster::factory()->worker()->make(['poller_groups' => '1,3', 'poller_workers' => 2, 'discovery_enabled' => false]);

    $queues = WorkerNode::queues($node);

    expect($queues['poller'])->toBe(['queues' => ['poll-1', 'poll-3'], 'workers' => 2]);
    expect($queues['discovery'])->toBeNull();
});

test('worker node settings', function () {
    // the dispatcher service is disabled when the scheduler dispatches work
    LibrenmsConfig::set('schedule_type.poller', WorkerNode::SCHEDULE_TYPE);
    LibrenmsConfig::set('schedule_type.discovery', WorkerNode::SCHEDULE_TYPE);

    $definition = collect(PollerCluster::factory()->worker()->make()->configDefinition(false))->keyBy('name');

    expect($definition->keys()->all())->toBe(['poller_groups', 'poller_enabled', 'poller_workers', 'discovery_enabled', 'discovery_workers']);
    expect($definition['poller_enabled']['default'])->toBeTrue();
    expect($definition['poller_enabled']['value'])->toBeTrue();
    expect($definition['discovery_enabled']['value'])->toBeTrue();
});

test('settings page shows worker settings', function () {
    $user = User::factory()->admin()->create(['enabled' => true]);
    PollerCluster::factory()->worker()->create(['poller_name' => 'worker-node']);

    $this->actingAs($user)->get(route('poller.settings'))
        ->assertOk()
        ->assertSee('worker-node')
        ->assertSee('poller_workers')
        ->assertDontSee('ping_frequency');
});

test('worker node rejects service settings', function () {
    $user = User::factory()->admin()->create(['enabled' => true]);
    $node = PollerCluster::factory()->worker()->create();

    $this->actingAs($user)->putJson(route('poller.settings.update', ['id' => $node->id, 'setting' => 'ping_frequency']), ['value' => 60])
        ->assertStatus(422);
    $this->actingAs($user)->putJson(route('poller.settings.update', ['id' => $node->id, 'setting' => 'poller_workers']), ['value' => 6])
        ->assertOk();

    expect($node->fresh()?->poller_workers)->toBe(6);
});

test('any worker records its queues', function () {
    // queue:work and Horizon's workers both fire Looping
    Event::dispatch(new Looping('redis', 'poll,poll-2'));

    expect(app(WorkedQueues::class)->worked(['scheduler', 'poll', 'poll-2', 'discovery']))->toBe(['poll', 'poll-2']);
});

test('records expire without workers', function () {
    $this->freezeSecond();
    app(WorkedQueues::class)->looping('poll');

    $this->travel(5)->minutes();

    expect(app(WorkedQueues::class)->worked(['poll']))->toBe([]);
});

test('busy worker still works its queues', function () {
    $this->freezeSecond();
    $busy = new WorkedQueues;
    $busy->looping('discovery');
    $job = Mockery::mock(Job::class);
    $job->shouldReceive('timeout')->andReturn(3600);
    $busy->jobStarted($job);

    // another process on the queue loops and then stops, it does not shorten the busy one's record
    (new WorkedQueues)->looping('discovery');
    $this->travel(30)->minutes();

    expect(app(WorkedQueues::class)->worked(['discovery']))->toBe(['discovery']);
});