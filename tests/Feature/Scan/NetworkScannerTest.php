<?php

use App\Actions\Device\AddFoundDeviceResult;
use App\Models\Device;
use App\Models\Secret;
use LibreNMS\Data\Source\Snmp\RawSnmpResponse;
use LibreNMS\Data\Source\Snmp\SnmpBackendInterface;
use LibreNMS\Enum\DeviceAddOutcome;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Enum\SecretType;
use LibreNMS\Scan\NetworkScanner;
use LibreNMS\Scan\ScanOptions;
use LibreNMS\Util\Dns;
use LibreNMS\Util\IP;

uses(
    \LibreNMS\Tests\DBTestCase::class,
    \Illuminate\Foundation\Testing\DatabaseTransactions::class,
    \LibreNMS\Tests\Traits\FakesNetworkTools::class,
);

test('Reports every address once, skips excluded and known addresses, pings in batches', function (): void {
    $log = $this->fakeFping(['10.0.0.5', '10.0.1.7']);
    Device::factory()->create(['hostname' => '10.0.0.9']);
    networkScannerMockDns(null);

    $results = networkScannerScan(['10.0.0.0/23', '10.0.2.0/24'], new ScanOptions(excludedNetworks: ['10.0.2.0/25'], pingFallback: true));

    expect($results)->toHaveCount(510 + 254, 'every usable address is reported once');
    expect($results['10.0.0.9'])->toBe(DeviceAddOutcome::Known);
    expect($results['10.0.2.1'])->toBe(DeviceAddOutcome::Excluded);
    expect($results['10.0.0.5'])->toBe(DeviceAddOutcome::Added);
    expect($results['10.0.1.7'])->toBe(DeviceAddOutcome::Added);
    expect($results['10.0.2.200'])->toBe(DeviceAddOutcome::Unpingable);
    expect(array_keys($results, DeviceAddOutcome::Unpingable, true))->toHaveCount(764 - 127 - 1 - 2, 'the rest are not excluded, known or added');
    expect(Device::where('hostname', '10.0.1.7')->exists())->toBeTrue();

    // batches of 256 addresses, without the known one in the first and the excluded ones in the others
    expect(file($log, FILE_IGNORE_NEW_LINES))->toBe(['255', '254', '127']);
});

test('Probes the addresses of overlapping networks once', function (): void {
    $log = $this->fakeFping(['10.0.0.5', '10.0.0.0']);
    networkScannerMockDns(null);

    // the /32 and the later /24 are in the first /24, the /31 shares only 10.0.0.0 with it
    $results = networkScannerScan(['10.0.0.0/24', '10.0.0.5/32', '10.0.0.0/24', '10.0.0.0/31'], new ScanOptions(pingFallback: true));

    expect($results)->toHaveCount(255);
    expect($results['10.0.0.5'])->toBe(DeviceAddOutcome::Added);
    expect($results['10.0.0.0'])->toBe(DeviceAddOutcome::Added);
    expect(file($log, FILE_IGNORE_NEW_LINES))->toBe(['255']);

    // a /31 given before a /30 with the same number of addresses
    $results = networkScannerScan(['10.0.1.0/31', '10.0.1.0/30'], new ScanOptions(pingFallback: true));
    expect(array_keys($results))->toBe(['10.0.1.0', '10.0.1.1', '10.0.1.2']);
});

test('Adds a host with the credential it replied to, without trying the others', function (): void {
    $this->fakeSnmpget();
    $this->fakeFping(['192.0.2.1']);
    networkScannerMockDns('scanned.example.com');
    $other = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'other']]);
    $replied = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'replied']]);

    // the add uses the SNMP backend, the probe uses snmpget
    $communities = [];
    $backend = Mockery::mock(SnmpBackendInterface::class);
    $backend->shouldReceive('get')->andReturnUsing(function ($target, $oids, $config) use (&$communities) {
        $communities[] = $config->community;

        return new RawSnmpResponse('SNMPv2-MIB::sysObjectID.0 = .1.3.6.1.4.1.8072.3.2.10', '', 0);
    });
    $backend->shouldIgnoreMissing(new RawSnmpResponse('', '', 0));
    $this->app->instance(SnmpBackendInterface::class, $backend);

    $configs = $this->fakeSnmpConfigs([$other->id => '0-1', $replied->id => '0-0']);
    $results = networkScannerScan(['192.0.2.1/32'], new ScanOptions(snmpConfigs: $configs));

    expect($results)->toBe(['192.0.2.1' => DeviceAddOutcome::Added]);
    $device = Device::where('hostname', 'scanned.example.com')->firstOrFail();
    expect($device->pollingMethod(PollingMethodType::Snmp)?->secret_id)->toBe($replied->id);
    expect(array_values(array_unique($communities)))->toBe(['replied']);
});

/**
 * @param  string[]  $networks
 * @return array<string, DeviceAddOutcome>
 */
function networkScannerScan(array $networks, ScanOptions $options): array
{
    $results = [];
    app(NetworkScanner::class)->scan(array_map(IP::parse(...), $networks), $options, function (string $ip, AddFoundDeviceResult $result) use (&$results): void {
        test()->assertArrayNotHasKey($ip, $results, "$ip is reported once");
        $results[$ip] = $result->outcome;
    });

    return $results;
}

function networkScannerMockDns(?string $hostname): void
{
    $dns = Mockery::mock(Dns::class);
    $dns->shouldReceive('resolveHostname')->andReturn($hostname);
    app()->instance(Dns::class, $dns);
}