<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Facades\LibrenmsConfig;
use App\Models\Callback;
use App\Models\Device;
use App\Models\DevicePollingMethod;
use App\Models\Secret;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Enum\SecretType;
use LibreNMS\Util\Stats;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('submits usage statistics when reporting is enabled', function () {
    Callback::where('name', 'enabled')->delete();
    LibrenmsConfig::set('reporting.usage', true);
    $endpoint = LibrenmsConfig::get('callback_post');
    Http::preventStrayRequests();
    Http::fake([$endpoint => Http::response()]);

    Stats::submit();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === $endpoint
        && isset(json_decode($request['data'], true)['uuid']));
});

test('does not submit usage statistics when reporting is disabled', function () {
    Callback::set('enabled', 1);
    LibrenmsConfig::set('reporting.usage', false);
    Http::preventStrayRequests();
    Http::fake([LibrenmsConfig::get('callback_post') => Http::response()]);

    Stats::submit();

    Http::assertNothingSent();
});

test('port association mode uses the default without an override', function () {
    LibrenmsConfig::set('default_port_association_mode', 'ifIndex');
    snmpDevice([]);
    snmpDevice([]);
    snmpDevice(['port_association_mode' => 'ifName']);
    DevicePollingMethod::factory()->create([
        'device_id' => Device::factory()->create()->device_id,
        'method_type' => PollingMethodType::Icmp, // ping only devices have no ports
    ]);

    $portAssoc = (new Stats)->dump()['data']['port_assoc'];

    expect($portAssoc)->toBeInstanceOf(Collection::class);
    expect($portAssoc->pluck('total', 'port_association_mode')->all())->toEqual([1 => 2, 2 => 1]);
    // ifIndex => 2, ifName => 1, reported as the mode id
});

test('snmp version follows the secret type and version setting', function () {
    $community = Secret::create(['description' => 'stats community', 'secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'public']]);
    $v3 = Secret::create(['description' => 'stats v3', 'secret_type' => SecretType::SnmpV3, 'data' => ['authlevel' => 'noAuthNoPriv', 'authname' => 'user']]);
    snmpDevice([], $community);
    snmpDevice(['version' => 'v2c'], $community);
    snmpDevice(['version' => 'v1', 'port' => 1161], $community);
    snmpDevice(['version' => 'v1'], $v3);
    // a v3 secret is always v3
    snmpDevice([], $v3);
    snmpDevice([]);

    // no secret, polled as v2c
    $versions = (new Stats)->dump()['data']['snmp_version'];

    expect($versions->pluck('total', 'snmpver')->all())->toEqualCanonicalizing(['v2c' => 3, 'v1' => 1, 'v3' => 2]);
});

/**
 * @param  array<string, mixed>  $settings
 */
function snmpDevice(array $settings, ?Secret $secret = null): void
{
    DevicePollingMethod::factory()->create([
        'device_id' => Device::factory()->create()->device_id,
        'method_type' => PollingMethodType::Snmp,
        'settings' => $settings,
        'secret_id' => $secret?->id,
    ]);
}