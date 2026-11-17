<?php

uses(\LibreNMS\Tests\InMemoryDbTestCase::class);
use App\Facades\DeviceCache;
use \LibreNMS\Tests\Feature\NoopJob;
use \LibreNMS\Tests\Feature\CompletingJob;
use App\Models\Device;
use App\PerDeviceProcess;
use App\Polling\Measure\MeasurementManager;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Event;
use LibreNMS\Enum\ProcessType;
use LibreNMS\Util\ModuleList;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;


test('down device shows reason', function () {
    $device = Device::factory()->create(['status' => 0, 'status_reason' => 'icmp']);

    $this->assertStringContainsString('Device was down (icmp), unable to poll.', runProcess((string) $device->device_id));
});

test('down devices without reason', function () {
    Device::factory()->create(['status' => 0, 'status_reason' => '']);
    $device = Device::factory()->create(['status' => 0, 'status_reason' => '']);

    $this->assertStringContainsString('Device was down (unknown reason), unable to poll.', runProcess((string) $device->device_id));
    $this->assertStringContainsString('All devices were down, unable to poll.', runProcess('all'));
});

test('each device gets fresh scoped instances', function () {
    $devices = Device::factory()->count(2)->create(['status' => 1]);
    CompletingJob::$deviceCaches = [];

    $process = new PerDeviceProcess(ProcessType::Poller, 'all', CompletingJob::class, 'test.completed', ModuleList::fromUserOverrides([]));
    $process->run();

    expect(CompletingJob::$deviceCaches)->toHaveCount($devices->count());
    expect(array_unique(CompletingJob::$deviceCaches))->toHaveCount($devices->count(), 'each device should get a new device cache');
});

function runProcess(string $spec): string
{
    // the job does nothing, so no device completes
    $process = new PerDeviceProcess(ProcessType::Poller, $spec, NoopJob::class, 'test.completed', ModuleList::fromUserOverrides([]));
    $process->run();

    $output = new BufferedOutput;
    expect($process->processResults(app(MeasurementManager::class), new OutputStyle(new ArrayInput([]), $output)))->toBe(6);

    return $output->fetch();
}

class NoopJob
{
    function handle(): void
    {
    }
}

class CompletingJob
{
    function __construct(private readonly int $device_id)
    {
    }

    function handle(): void
    {
        self::$deviceCaches[] = spl_object_id(DeviceCache::getFacadeRoot());
        Event::dispatch('test.completed', [(object) ['device' => Device::find($this->device_id)]]);
    }
}
