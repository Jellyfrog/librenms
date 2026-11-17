<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Actions\Device\AddFoundDeviceResult;
use App\Events\NetworkAddressScanned;
use App\Facades\LibrenmsConfig;
use App\Jobs\ScanNetworks;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use LibreNMS\Enum\DeviceAddOutcome;
use LibreNMS\Scan\NetworkScanner;
use LibreNMS\Scan\ScanOptions;
use LibreNMS\Util\IP;
use PHPUnit\Framework\Attributes\TestDox;


test('reports results', function () {
    Event::fake([NetworkAddressScanned::class]);
    LibrenmsConfig::set('nets', ['192.0.2.0/30']);
    LibrenmsConfig::set('autodiscovery.nets-exclude', ['198.51.100.0/24', 'invalid']);
    LibrenmsConfig::set('snmp.default_credentials', []);

    $scanner = Mockery::mock(NetworkScanner::class);
    $scanner->shouldReceive('scan')->once()->withArgs(function (array $networks, ScanOptions $options, callable $onResult): bool {
        expect($networks)->toEqual([IP::parse('192.0.2.0/30')]);
        expect($options->excludedNetworks)->toBe(['198.51.100.0/24']);
        expect($options->pollerGroup)->toBe(3);
        expect($options->pingFallback)->toBeTrue();

        $onResult('192.0.2.1', new AddFoundDeviceResult(DeviceAddOutcome::Unreachable));
        $onResult('192.0.2.2', new AddFoundDeviceResult(DeviceAddOutcome::Known, 'known'));

        return true;
    });
    $this->app->instance(NetworkScanner::class, $scanner);

    Bus::dispatchSync(new ScanNetworks('scan-1', pollerGroup: 3, pingFallback: true));

    Event::assertDispatchedTimes(NetworkAddressScanned::class, 2);
    Event::assertDispatched(NetworkAddressScanned::class, fn (NetworkAddressScanned $event) => $event->scanId === 'scan-1'
        && $event->ip === '192.0.2.2'
        && $event->result->outcome === DeviceAddOutcome::Known);
});

test('queue', function () {
    expect((new ScanNetworks('scan'))->queue)->toBe('discovery');
    expect((new ScanNetworks('scan', pollerGroup: 2))->queue)->toBe('discovery-2');
});

test('serializable', function () {
    $job = unserialize(serialize(new ScanNetworks('scan', ['192.0.2.0/24'], pingFallback: true)));

    expect($job->networks)->toBe(['192.0.2.0/24']);
    expect($job->pingFallback)->toBeTrue();
});
