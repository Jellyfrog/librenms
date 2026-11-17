<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Actions\Device\CheckDeviceAvailability;
use App\Events\DiscoveringModule;
use App\Exceptions\DiscoveryFailedException;
use App\Facades\LibrenmsConfig;
use App\Jobs\DiscoverDevice;
use App\Jobs\DispatchPollingWork;
use App\Jobs\PollDevice;
use App\Models\Device;
use App\Models\DevicePollingMethod;
use Illuminate\Queue\Jobs\FakeJob;
use Illuminate\Support\Facades\Event;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Polling\Method\Methods\UnixAgentPollingMethod;
use LibreNMS\Polling\PerDeviceMethodResults;
use LibreNMS\Util\ModuleList;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);
uses(\LibreNMS\Tests\Traits\FakesPollingMethods::class);

test('down device is retried and not marked as discovered', function (): void {
    $device = Device::factory()->create(['hostname' => '192.0.2.1', 'status' => 0, 'last_discovered' => null]);
    fakeAvailability(false);

    try {
        new DiscoverDevice($device->device_id, ModuleList::fromUserOverrides([]))->withFakeQueueInteractions()->handle();
        $this->fail('A down device should be retried');
    } catch (DiscoveryFailedException) {
        expect($device->fresh()->last_discovered)->toBeNull('a down device should not be marked as discovered');
    }
});

test('down device is not retried on the last attempt', function (): void {
    $device = Device::factory()->create(['hostname' => '192.0.2.1', 'status' => 0, 'last_discovered' => null]);
    fakeAvailability(false);

    $job = new DiscoverDevice($device->device_id, ModuleList::fromUserOverrides([]));
    $lastAttempt = new FakeJob;
    $lastAttempt->attempts = $job->tries;
    $job->setJob($lastAttempt)->handle();

    expect($device->fresh()->last_discovered)->toBeNull('a down device should not be marked as discovered');
});

test('down device is not retried when run directly', function (): void {
    $device = Device::factory()->create(['hostname' => '192.0.2.1', 'status' => 0, 'last_discovered' => null]);
    fakeAvailability(false);

    new DiscoverDevice($device->device_id, ModuleList::fromUserOverrides([]))->handle();

    expect($device->fresh()->last_discovered)->toBeNull('a down device should not be marked as discovered');
});

test('up device is marked as discovered', function (): void {
    $this->freezeSecond();
    $device = Device::factory()->create(['hostname' => '192.0.2.1', 'status' => 1, 'last_discovered' => null]);
    fakeAvailability(true);

    new DiscoverDevice($device->device_id, ModuleList::fromUserOverrides(['availability']))->withFakeQueueInteractions()->handle();

    $device->refresh();
    expect($device->last_discovered)->toEqual(now());
    expect($device->last_discovered_timetaken)->not->toBeNull();
});

test('device module setting overrides polling method', function (?int $setting, bool $expected): void {
    Event::fake([DiscoveringModule::class]);
    LibrenmsConfig::set('discovery_modules', ['mempools' => false]);
    $device = Device::factory()->create(['hostname' => '192.0.2.1', 'status' => 1]);
    DevicePollingMethod::factory()->for($device)->create(['method_type' => PollingMethodType::UnixAgent]);
    if ($setting !== null) {
        $device->setAttrib('discover_mempools', $setting);
    }
    fakeAvailability(true);
    $this->fakeMethod(UnixAgentPollingMethod::class, true, ['output' => $this->agentMemOutput(100, 50)]);

    new DiscoverDevice($device->device_id, ModuleList::fromUserOverrides([]))->handle();

    $events = Event::dispatched(DiscoveringModule::class, fn (DiscoveringModule $event): bool => $event->module === 'mempools');
    expect($events)->toHaveCount($expected ? 1 : 0);
    expect($device->mempools()->count())->toBe($expected ? 1 : 0);
})->with('deviceModuleSettings');

dataset('deviceModuleSettings', function () {
    return [
        'polling method enables globally disabled module' => [null, true],
        'device disables module enabled by polling method' => [0, false],
        'device explicitly enables module' => [1, true],
    ];
});

test('deleted device is skipped', function (): void {
    $device = Device::factory()->create();
    $device_id = $device->device_id;
    $device->delete();
    $count = Device::count();

    new DiscoverDevice($device_id, ModuleList::fromUserOverrides([]))->withFakeQueueInteractions()->handle();
    new PollDevice($device_id, ModuleList::fromUserOverrides([]))->withFakeQueueInteractions()->handle();

    expect(Device::count())->toBe($count, 'a deleted device should not be recreated');
});

test('device moved to another poller group is skipped', function (): void {
    $device = Device::factory()->create(['hostname' => '192.0.2.1', 'poller_group' => 2]);
    $availability = fakeAvailability(true);
    $modules = ModuleList::fromUserOverrides(['availability']);

    new DiscoverDevice($device->device_id, $modules)->onQueue('discovery-1')->withFakeQueueInteractions()->handle();
    new PollDevice($device->device_id, $modules)->onQueue('poll')->withFakeQueueInteractions()->handle();

    expect($availability->checks)->toBe(0, 'a job queued for the old poller group should not run');
});

test('unique per queue', function (): void {
    $modules = ModuleList::fromUserOverrides([]);

    $this->assertNotEquals(
        new PollDevice(1, $modules)->onQueue('poll-1')->uniqueId(),
        new PollDevice(1, $modules)->onQueue('poll-2')->uniqueId(),
    );
    $this->assertNotEquals(
        new DiscoverDevice(1, $modules)->onQueue('discovery')->uniqueId(),
        new DiscoverDevice(1, $modules)->onQueue('discovery-2')->uniqueId(),
    );
});

test('retries match polling', function (): void {
    $discover = new DiscoverDevice(1, ModuleList::fromUserOverrides([]));
    $poll = new PollDevice(1, ModuleList::fromUserOverrides([]));

    expect($discover->backoff())->toBe($poll->backoff());
    expect($discover->tries)->toBe($poll->tries);
    expect($discover->backoff())->toHaveCount($discover->tries - 1);
});

test('poll lock covers retries', function (): void {
    $poll = new PollDevice(1, ModuleList::fromUserOverrides([]));

    // a slow poll does not outlast it, a lock left behind holds up the device for a timeout and a poll interval at most
    expect($poll->uniqueFor())->toBe($poll->timeout + DispatchPollingWork::pollFrequency());
    expect($poll->uniqueFor())->toBeGreaterThan(array_sum($poll->backoff()) + DispatchPollingWork::pollInterval(), 'retries of a down device');
});

function fakeAvailability(bool $up): object
{
    return app()->instance(CheckDeviceAvailability::class, new class($up)
    {
        public int $checks = 0;

        public function __construct(private readonly bool $up)
        {
        }

        public function execute(PerDeviceMethodResults $methodResults, bool $commit = false): bool
        {
            $device = $methodResults->device;
            $this->checks++;
            $device->status = $this->up;
            $device->status_reason = $this->up ? '' : 'icmp';

            return $this->up;
        }
    });
}