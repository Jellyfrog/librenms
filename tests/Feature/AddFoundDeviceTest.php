<?php

use App\Actions\Device\AddFoundDevice;
use App\Actions\Device\AutoDiscoverDevice;
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\Eventlog;
use App\Models\Secret;
use LibreNMS\Data\Source\Icmp\Fping;
use LibreNMS\Data\Source\Icmp\FpingResponse;
use LibreNMS\Data\Source\Snmp\RawSnmpResponse;
use LibreNMS\Data\Source\Snmp\SnmpBackendInterface;
use LibreNMS\Enum\DeviceAddOutcome;
use LibreNMS\Enum\SecretType;

uses(\LibreNMS\Tests\DBTestCase::class, \Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('A device that does not reply to SNMP is unreachable, with the reason', function (): void {
    mockNoSnmpReply();

    $result = app(AddFoundDevice::class)->execute(new Device(['hostname' => 'no-snmp.example.com']));

    expect($result->outcome)->toBe(DeviceAddOutcome::Unreachable);
    $this->assertStringContainsString('No reply using credential "scan default"', $result->message);
    expect(Device::where('hostname', 'no-snmp.example.com')->exists())->toBeFalse();
});

test('Autodiscovery logs why a neighbor was not added', function (): void {
    mockNoSnmpReply();
    LibrenmsConfig::set('nets', ['192.0.2.0/24']);
    LibrenmsConfig::set('discovery_by_ip', true);
    $source = Device::factory()->create();

    expect(app(AutoDiscoverDevice::class)->execute('192.0.2.20', $source, 'LLDP'))->toBeNull();

    $message = Eventlog::where('device_id', $source->device_id)->where('type', 'discovery')->value('message');
    expect((string) $message)->toStartWith('LLDP discovery of 192.0.2.20 (192.0.2.20) failed - Could not connect to 192.0.2.20 using SNMP');
});

function mockNoSnmpReply(): void
{
    $secret = Secret::factory()->create([
        'secret_type' => SecretType::SnmpCommunity,
        'description' => 'scan default',
        'data' => ['community' => 'public'],
    ]);
    LibrenmsConfig::set('snmp.default_credentials', [$secret->id]);

    $fping = Mockery::mock(Fping::class);
    $fping->shouldReceive('ping')->andReturn(FpingResponse::artificialUp());
    app()->instance(Fping::class, $fping);

    $backend = Mockery::mock(SnmpBackendInterface::class);
    $backend->shouldReceive('get')->andReturn(new RawSnmpResponse('', 'Timeout', 1));
    app()->instance(SnmpBackendInterface::class, $backend);
}