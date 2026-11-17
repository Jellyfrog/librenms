<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Actions\Device\BuildDefaultPollingMethods;
use App\Actions\Device\DiscoverDevicePollingMethods;
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\Secret;
use LibreNMS\Data\Source\Icmp\Fping;
use LibreNMS\Data\Source\Icmp\FpingResponse;
use LibreNMS\Data\Source\Snmp\SnmpBackendInterface;
use LibreNMS\Data\Source\Snmp\SnmpResponse;
use LibreNMS\Enum\SecretType;
use LibreNMS\Exceptions\HostUnreachableException;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('snmp reports every credential tried', function () {
    $ids = collect(['public', 'private'])->map(fn (string $community) => Secret::create([
        'description' => "Default $community",
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => $community],
    ])->id);
    LibrenmsConfig::set('snmp.default_credentials', $ids->all());

    $backend = Mockery::mock(SnmpBackendInterface::class);
    $backend->shouldReceive('get')->andReturn(new SnmpResponse([], 'Timeout', 1));
    $this->app->instance(SnmpBackendInterface::class, $backend);

    expect(discoverReasons(['snmp' => ['active' => true]]))->toBe([
        'SNMP v2c: No reply using credential "Default public"',
        'SNMP v2c: No reply using credential "Default private"',
        'SNMP v1: No reply using credential "Default public"',
        'SNMP v1: No reply using credential "Default private"',
    ]);
});

test('icmp reports error message', function () {
    $fping = Mockery::mock(Fping::class);
    $fping->shouldReceive('ping')->andReturn(FpingResponse::artificialDown('192.0.2.1'));
    $this->app->instance(Fping::class, $fping);

    expect(discoverReasons(['icmp' => ['active' => true]]))->toBe(['192.0.2.1 : xmt/rcv/%loss = 1/0/100%']);
});

/**
 * @param  array<string, array<string, mixed>>  $methods
 * @return string[]
 */
function discoverReasons(array $methods): array
{
    $device = new Device(['hostname' => '192.0.2.1']);
    $pollingMethods = app(BuildDefaultPollingMethods::class)->execute($device, ['methods' => $methods]);

    try {
        app(DiscoverDevicePollingMethods::class)->execute($device, $pollingMethods);
    } catch (HostUnreachableException $e) {
        return $e->getReasons();
    }

    $this->fail('Expected HostUnreachableException');
}