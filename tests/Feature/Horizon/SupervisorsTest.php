<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use App\Horizon\Supervisors;
use App\Models\PollerCluster;
use App\Queue\WorkerNode;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\ProvisioningPlan;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('supervisors for node settings', function () {
    LibrenmsConfig::set('distributed_poller_group', '0');
    LibrenmsConfig::set('service_discovery_workers', 4);
    $node = PollerCluster::factory()->worker()->make(['poller_groups' => '1,3', 'poller_workers' => 2]);

    expect(Supervisors::forNode($node))->toBe([
        'supervisor-poll' => ['queue' => ['poll-1', 'poll-3'], 'maxProcesses' => 2],
        'supervisor-discovery' => ['queue' => ['discovery-1', 'discovery-3'], 'maxProcesses' => 4],
    ]);
});

test('disabled polling does not start asupervisor', function () {
    $node = PollerCluster::factory()->worker()->make(['poller_enabled' => false]);

    app(Supervisors::class)->start($node);

    $plan = new ProvisioningPlan('test', config('horizon.environments'), config('horizon.defaults'))->toSupervisorOptions();
    $this->assertArrayNotHasKey('supervisor-poll', $plan['*']);
    expect($plan['*'])->toHaveKey('supervisor-discovery');
});

test('supervisors are configured when horizon starts', function () {
    LibrenmsConfig::set('distributed_poller_group', '0');
    $node = PollerCluster::factory()->worker()->create(['poller_groups' => '4', 'poller_workers' => 3]);

    // CommandStartingListener also runs, it rejects users other than the LibreNMS user
    config(['librenms.node_id' => $node->node_id, 'librenms.user' => posix_getpwuid(posix_geteuid())['name']]);

    Event::dispatch(new CommandStarting('horizon:status', new ArrayInput([]), new NullOutput));
    expect(config('horizon.defaults.supervisor-poll.queue'))->toBe(['poll'], 'other commands should not configure supervisors');

    Event::dispatch(new CommandStarting('horizon', new ArrayInput([]), new NullOutput));
    expect(config('horizon.defaults.supervisor-poll.queue'))->toBe(['poll-4']);
    expect(config('horizon.defaults.supervisor-poll.maxProcesses'))->toBe(3);
    expect(config('horizon.defaults.supervisor-discovery.queue'))->toBe(['discovery-4']);
});

test('worker command runs horizon', function () {
    expect(\Illuminate\Support\Facades\Artisan::all()['worker'] ?? null)->toBeInstanceOf(\Laravel\Horizon\Console\HorizonCommand::class);
});

test('changed settings stop horizon', function () {
    $node = PollerCluster::factory()->worker()->create(['poller_groups' => '1']);
    config(['librenms.node_id' => $node->node_id]);
    $supervisors = new Supervisors;
    $supervisors->start();

    $node->poller_groups = '2';
    $node->save();

    // the service starts it again with the new settings
    $master = Mockery::mock(MasterSupervisor::class);
    $master->shouldReceive('terminate')->once();

    $supervisors->looped($master);
    $supervisors->looped($master);
    // within the heartbeat interval, nothing to do
});

test('unchanged settings keep horizon running', function () {
    $node = PollerCluster::factory()->worker()->create(['poller_groups' => '1']);
    config(['librenms.node_id' => $node->node_id]);
    $supervisors = new Supervisors;
    $supervisors->start();
    $master = Mockery::mock(MasterSupervisor::class);
    $master->shouldNotReceive('terminate');

    $supervisors->looped($master);
});

test('master checks in the node', function () {
    $this->freezeSecond();
    config(['librenms.node_id' => 'worker-node-test']);
    LibrenmsConfig::set('distributed_poller_name', 'poller1.example.com');
    $supervisors = new Supervisors;
    $supervisors->start();
    $master = Mockery::mock(MasterSupervisor::class);
    $master->shouldNotReceive('terminate');

    $supervisors->looped($master);

    $node = PollerCluster::firstWhere('node_id', 'worker-node-test');
    expect($node)->not->toBeNull();
    expect($node->poller_version)->toBe(WorkerNode::VERSION);
    expect($node->poller_name)->toBe('poller1.example.com');
    expect($node->poller_groups)->toBe((string) LibrenmsConfig::get('distributed_poller_group'));
    expect($node->last_report)->toEqual(now());
    expect($node->poller_details['php_version'] ?? null)->toBe(PHP_VERSION);
    expect($node->stats)->toHaveCount(0, 'queue worker nodes share their queues, there are no per node stats');
});

test('masters are named for the node', function () {
    config(['librenms.node_id' => '6a1f2b3c4d5e6']);

    expect(MasterSupervisor::basename())->toBe('6a1f2b3c4d5e6');
    expect(MasterSupervisor::name())->toStartWith('6a1f2b3c4d5e6-');
});