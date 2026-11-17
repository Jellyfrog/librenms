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
use PHPUnit\Framework\Attributes\TestDox;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('no dns', function () {
    mockDns(null);

    $result = Bus::dispatchNow(new AddScannedHost('192.0.2.10', snmpSecretId: null, pingable: true, dnsOnly: true));

    expect($result->outcome)->toBe(DeviceAddOutcome::NoDns);
    expect(Device::where('hostname', '192.0.2.10')->exists())->toBeFalse();
});

test('added ping only', function () {
    mockDns('scanned.example.com');
    $this->app->instance(Fping::class, Mockery::mock(Fping::class)->shouldNotReceive('ping')->getMock());

    $result = Bus::dispatchNow(new AddScannedHost('192.0.2.11', snmpSecretId: null, pingable: true));

    expect($result->outcome)->toBe(DeviceAddOutcome::Added);
    $device = Device::where('hostname', 'scanned.example.com')->firstOrFail();
    expect($device->os)->toBe('ping');
    expect($device->polling()->isEnabled(PollingMethodType::Icmp))->toBeTrue();
    expect($device->polling()->isEnabled(PollingMethodType::Snmp))->toBeFalse();
});

test('unpingable', function () {
    mockDns(null);
    mockFping(FpingResponse::artificialDown());

    $result = Bus::dispatchNow(new AddScannedHost('192.0.2.12', snmpSecretId: null, pingable: false));

    expect($result->outcome)->toBe(DeviceAddOutcome::Unpingable);
});

test('added with secret', function () {
    $other = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'other']]);
    $replied = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'replied']]);
    LibrenmsConfig::set('snmp.default_credentials', [$other->id, $replied->id]);
    mockDns('snmp.example.com');

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

test('added with the version it replied to', function () {
    $secret = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'replied']]);
    LibrenmsConfig::set('snmp.default_credentials', [$secret->id]);
    mockDns('v1.example.com');

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

test('added without icmp', function () {
    $secret = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'replied']]);
    LibrenmsConfig::set('snmp.default_credentials', [$secret->id]);
    mockDns('noping.example.com');
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

test('known', function () {
    $device = Device::factory()->create();
    mockDns($device->hostname);

    $result = Bus::dispatchNow(new AddScannedHost('192.0.2.13', snmpSecretId: null, pingable: true));

    expect($result->outcome)->toBe(DeviceAddOutcome::Known);
});

function mockDns(?string $hostname): void
{
    $dns = Mockery::mock(Dns::class);
    $dns->shouldReceive('resolveHostname')->andReturn($hostname);
    $this->app->instance(Dns::class, $dns);
}

function mockFping(FpingResponse $response): void
{
    $fping = Mockery::mock(Fping::class);
    $fping->shouldReceive('ping')->andReturn($response);
    $this->app->instance(Fping::class, $fping);
}