<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Facades\LibrenmsConfig;
use App\Jobs\AddScannedHost;
use App\Models\Device;
use App\Models\Secret;
use Illuminate\Support\Facades\Bus;
use LibreNMS\Data\Source\Icmp\Fping;
use LibreNMS\Data\Source\Icmp\FpingResponse;
use LibreNMS\Data\Source\Snmp\RawSnmpResponse;
use LibreNMS\Data\Source\Snmp\SnmpBackendInterface;
use LibreNMS\Enum\DeviceAddOutcome;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Enum\SecretType;
use LibreNMS\Util\Dns;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('Skips a host without reverse DNS when DNS is required', function (): void {
    mockScannedHostDns(null);

    $result = Bus::dispatchNow(new AddScannedHost('192.0.2.10', snmpSecretId: null, pingable: true, dnsOnly: true));

    expect($result->outcome)->toBe(DeviceAddOutcome::NoDns);
    expect(Device::where('hostname', '192.0.2.10')->exists())->toBeFalse();
});

test('Adds a host that replied to ping as ping only without pinging it again', function (): void {
    mockScannedHostDns('scanned.example.com');
    $this->app->instance(Fping::class, Mockery::mock(Fping::class)->shouldNotReceive('ping')->getMock());

    $result = Bus::dispatchNow(new AddScannedHost('192.0.2.11', snmpSecretId: null, pingable: true));

    expect($result->outcome)->toBe(DeviceAddOutcome::Added);
    $device = Device::where('hostname', 'scanned.example.com')->firstOrFail();
    expect($device->os)->toBe('ping');
    expect($device->polling()->isEnabled(PollingMethodType::Icmp))->toBeTrue();
    expect($device->polling()->isEnabled(PollingMethodType::Snmp))->toBeFalse();
});

test('A host that was not pinged is pinged before it is added', function (): void {
    mockScannedHostDns(null);
    mockScannedHostFping(FpingResponse::artificialDown());

    $result = Bus::dispatchNow(new AddScannedHost('192.0.2.12', snmpSecretId: null, pingable: false));

    expect($result->outcome)->toBe(DeviceAddOutcome::Unpingable);
});

test('Adds a host with the secret it replied to, the other default secrets are not tried', function (): void {
    $other = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'other']]);
    $replied = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'replied']]);
    LibrenmsConfig::set('snmp.default_credentials', [$other->id, $replied->id]);
    mockScannedHostDns('snmp.example.com');

    $communities = [];
    $backend = Mockery::mock(SnmpBackendInterface::class);
    $backend->shouldReceive('get')->andReturnUsing(function ($target, $oids, $config) use (&$communities) {
        $communities[] = $config->community;

        return new RawSnmpResponse('SNMPv2-MIB::sysObjectID.0 = .1.3.6.1.4.1.8072.3.2.10', '', 0);
    });
    $backend->shouldIgnoreMissing(new RawSnmpResponse('', '', 0));
    $this->app->instance(SnmpBackendInterface::class, $backend);

    $result = Bus::dispatchNow(new AddScannedHost('192.0.2.14', snmpSecretId: $replied->id, pingable: true));

    expect($result->outcome)->toBe(DeviceAddOutcome::Added, $result->message);
    $device = Device::where('hostname', 'snmp.example.com')->firstOrFail();
    expect($device->pollingMethod(PollingMethodType::Snmp)?->secret_id)->toBe($replied->id);
    expect(array_values(array_unique($communities)))->toBe(['replied']);
});

test('A host that replied to v1 is added as v1, v2c is not tried again', function (): void {
    $secret = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'replied']]);
    LibrenmsConfig::set('snmp.default_credentials', [$secret->id]);
    mockScannedHostDns('v1.example.com');

    $versions = [];
    $backend = Mockery::mock(SnmpBackendInterface::class);
    $backend->shouldReceive('get')->andReturnUsing(function ($target, $oids, $config) use (&$versions) {
        $versions[] = $config->version;

        return new RawSnmpResponse('SNMPv2-MIB::sysObjectID.0 = .1.3.6.1.4.1.8072.3.2.10', '', 0);
    });
    $backend->shouldIgnoreMissing(new RawSnmpResponse('', '', 0));
    $this->app->instance(SnmpBackendInterface::class, $backend);

    $result = Bus::dispatchNow(new AddScannedHost('192.0.2.16', snmpSecretId: $secret->id, pingable: true, snmpVersion: 'v1'));

    expect($result->outcome)->toBe(DeviceAddOutcome::Added, $result->message);
    $device = Device::where('hostname', 'v1.example.com')->firstOrFail();
    expect($device->pollingMethod(PollingMethodType::Snmp)?->settings['version'] ?? null)->toBe('v1');
    expect(array_values(array_unique($versions)))->toBe(['v1']);
});

test('A host that replied to SNMP but not ping is added without ICMP and not pinged again', function (): void {
    $secret = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'replied']]);
    LibrenmsConfig::set('snmp.default_credentials', [$secret->id]);
    mockScannedHostDns('noping.example.com');
    $this->app->instance(Fping::class, Mockery::mock(Fping::class)->shouldNotReceive('ping')->getMock());

    $backend = Mockery::mock(SnmpBackendInterface::class);
    $backend->shouldReceive('get')->andReturn(new RawSnmpResponse('SNMPv2-MIB::sysObjectID.0 = .1.3.6.1.4.1.8072.3.2.10', '', 0));
    $backend->shouldIgnoreMissing(new RawSnmpResponse('', '', 0));
    $this->app->instance(SnmpBackendInterface::class, $backend);

    $result = Bus::dispatchNow(new AddScannedHost('192.0.2.15', snmpSecretId: $secret->id, pingable: false));

    expect($result->outcome)->toBe(DeviceAddOutcome::Added, $result->message);
    $device = Device::where('hostname', 'noping.example.com')->firstOrFail();
    expect($device->pollingMethod(PollingMethodType::Icmp))->toBeNull();
    expect($device->polling()->isEnabled(PollingMethodType::Snmp))->toBeTrue();
});

test('An existing device is reported as known', function (): void {
    $device = Device::factory()->create();
    mockScannedHostDns($device->hostname);

    $result = Bus::dispatchNow(new AddScannedHost('192.0.2.13', snmpSecretId: null, pingable: true));

    expect($result->outcome)->toBe(DeviceAddOutcome::Known);
});

function mockScannedHostDns(?string $hostname): void
{
    $dns = Mockery::mock(Dns::class);
    $dns->shouldReceive('resolveHostname')->andReturn($hostname);
    app()->instance(Dns::class, $dns);
}

function mockScannedHostFping(FpingResponse $response): void
{
    $fping = Mockery::mock(Fping::class);
    $fping->shouldReceive('ping')->andReturn($response);
    app()->instance(Fping::class, $fping);
}