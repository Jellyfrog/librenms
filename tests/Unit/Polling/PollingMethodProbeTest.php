<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\DevicePollingMethod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use LibreNMS\Data\Source\Icmp\Fping;
use LibreNMS\Data\Source\Icmp\FpingResponse;
use LibreNMS\Data\Source\Snmp\RawSnmpResponse;
use LibreNMS\Data\Source\Snmp\SnmpBackendInterface;
use LibreNMS\Data\Source\Snmp\SnmpQueryOptions;
use LibreNMS\Enum\AddressFamily;
use LibreNMS\Enum\FpingExitCode;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Polling\Method\Config\IcmpConfig;
use LibreNMS\Polling\Method\Config\ProxmoxApiConfig;
use LibreNMS\Polling\Method\Config\SnmpConfig;
use LibreNMS\Polling\Method\Config\UnixAgentConfig;
use LibreNMS\Polling\Method\Methods\IcmpPollingMethod;
use LibreNMS\Polling\Method\Methods\ProxmoxApiPollingMethod;
use LibreNMS\Polling\Method\Methods\SnmpPollingMethod;
use LibreNMS\Polling\Method\Methods\UnixAgentPollingMethod;
use LibreNMS\Polling\Secrets\Data\SnmpCommunitySecretData;
use PHPUnit\Framework\Attributes\DataProvider;


test('icmp pings with configured address family', function (string $ipVersion, string $snmpTransport, ?AddressFamily $expected) {
    $device = new Device(['hostname' => '192.0.2.1']);
    $device->setRelation('pollingMethods', new Collection([
        new DevicePollingMethod(['method_type' => PollingMethodType::Snmp, 'settings' => ['transport' => $snmpTransport]]),
    ]));

    $fping = Mockery::mock(Fping::class);
    $fping->shouldReceive('ping')->with('192.0.2.1', $expected)->once()->andReturn(FpingResponse::artificialUp('192.0.2.1'));
    $this->app->instance(Fping::class, $fping);

    $result = app(IcmpPollingMethod::class)->probe($device, new IcmpConfig(ipVersion: $ipVersion));

    expect($result->isSuccess())->toBeTrue();
})->with('icmpAddressFamilyProvider');

/**
 * @return array<string, array{string, string, ?AddressFamily}>
 */
dataset('icmpAddressFamilyProvider', function () {
    return [
        'auto' => ['default', 'udp6', null],
        'ipv4' => ['ipv4', 'udp6', AddressFamily::IPv4],
        'ipv6' => ['ipv6', 'udp', AddressFamily::IPv6],
        'match snmp ipv4' => ['match_snmp_transport', 'tcp', AddressFamily::IPv4],
        'match snmp ipv6' => ['match_snmp_transport', 'udp6', AddressFamily::IPv6],
    ];
});

test('icmp discover reports unreachable', function () {
    $device = new Device(['hostname' => '192.0.2.1']);
    $device->setRelation('pollingMethods', new Collection);

    $fping = Mockery::mock(Fping::class);
    $fping->shouldReceive('ping')->once()->andReturn(FpingResponse::createError(FpingExitCode::Unreachable, '192.0.2.1'));
    $this->app->instance(Fping::class, $fping);

    $result = app(IcmpPollingMethod::class)->discover($device, new DevicePollingMethod(['method_type' => PollingMethodType::Icmp]));

    expect($result->isSuccess())->toBeFalse();
    expect($result->errorMessage())->not->toBeNull();
});

test('snmp probes the device with the given config', function () {
    $device = new Device(['hostname' => 'snmp.test.local']);
    $config = SnmpConfig::make(['port' => 1161] + (new SnmpPollingMethod)->defaults(), new SnmpCommunitySecretData(community: 'custom-community'));

    $backend = Mockery::mock(SnmpBackendInterface::class);
    $backend->shouldReceive('get')
        ->with('snmp.test.local', ['SNMPv2-MIB::sysObjectID.0'], $config, Mockery::type(SnmpQueryOptions::class))
        ->once()
        ->andReturn(new RawSnmpResponse('SNMPv2-MIB::sysObjectID.0 = OID: SNMPv2-SMI::enterprises.9.1.1', '', 0));

    $result = new SnmpPollingMethod($backend)->probe($device, $config);

    expect($result->isSuccess())->toBeTrue();
    expect($result->errorMessage())->toBeNull();
});

test('snmp probe failure has error message', function () {
    $backend = Mockery::mock(SnmpBackendInterface::class);
    $backend->shouldReceive('get')->once()->andReturn(new RawSnmpResponse('', 'Timeout: No Response from snmp.test.local', 1));

    $result = new SnmpPollingMethod($backend)->probe(new Device(['hostname' => 'snmp.test.local']), SnmpConfig::make((new SnmpPollingMethod)->defaults(), new SnmpCommunitySecretData));

    expect($result->isSuccess())->toBeFalse();
    expect($result->errorMessage())->not->toBeEmpty();
});

test('unix agent probe uses configured port and timeout', function () {
    $config = new UnixAgentConfig(port: 1, timeout: 1);

    // nothing listens on port 1
    $result = (new UnixAgentPollingMethod)->probe(new Device(['hostname' => '127.0.0.1']), $config);

    expect($result->isSuccess())->toBeFalse();
    expect($result->stat('port'))->toBe(1);
    expect($result->stat('timeout'))->toBe(1);
});

test('proxmox probe reads version with token', function () {
    Http::fake([
        'https://pve.example.com:8006/api2/json/version' => Http::response(['data' => ['version' => '8.2.4', 'release' => '8.2']]),
        'https://pve.example.com:8006/api2/json/nodes/localhost/certificates/info' => Http::response(['data' => [['filename' => 'pve-root-ca.pem', 'fingerprint' => 'AB:CD:EF']]]),
    ]);

    $result = (new ProxmoxApiPollingMethod)->probe(new Device(['hostname' => 'pve.example.com']), proxmoxConfig());

    expect($result->isSuccess())->toBeTrue();
    expect($result->stat('version'))->toBe('8.2.4');
    expect($result->stat('cluster_id'))->toBe('proxmox-abcdef');
    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'PVEAPIToken=librenms@pve!monitoring=abc123'));
});

test('proxmox probe fails without aroot ca', function () {
    Http::fake(['*/version' => Http::response(['data' => ['version' => '8.2.4']]), '*/certificates/info' => Http::response(['data' => []])]);

    $result = (new ProxmoxApiPollingMethod)->probe(new Device(['hostname' => 'pve.example.com']), proxmoxConfig());

    expect($result->isSuccess())->toBeFalse();
});

test('proxmox probe fails with bad token', function () {
    Http::fake(['*' => Http::response(null, 401)]);

    $result = (new ProxmoxApiPollingMethod)->probe(new Device(['hostname' => 'pve.example.com']), proxmoxConfig());

    expect($result->isSuccess())->toBeFalse();
    expect($result->errorMessage())->not->toBeNull();
});

test('proxmox probe fails without token', function () {
    Http::fake();

    $result = (new ProxmoxApiPollingMethod)->probe(new Device(['hostname' => 'pve.example.com']), proxmoxConfig(tokenSecret: ''));

    expect($result->isSuccess())->toBeFalse();
    Http::assertNothingSent();
});

function proxmoxConfig(string $tokenSecret = 'abc123'): ProxmoxApiConfig
{
    return new ProxmoxApiConfig(
        hostname: 'pve.example.com',
        port: 8006,
        tokenId: 'librenms@pve!monitoring',
        tokenSecret: $tokenSecret,
        timeout: 5,
        verifySsl: false,
    );
}
