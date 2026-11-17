<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Actions\Device\DiscoverPollingMethod;
use App\Actions\Device\LegacyDeviceCreator;
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\Secret;
use LibreNMS\Data\Source\Snmp\RawSnmpResponse;
use LibreNMS\Data\Source\Snmp\SnmpBackendInterface;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Enum\SecretType;
use LibreNMS\Polling\Method\Config\SnmpConfig;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('creates device and polling methods for v2c', function (): void {
    $creator = new LegacyDeviceCreator(
        hostname: 'router1.example.com',
        display_template: '{{ $hostname }}',
        poller_group: 2,
        snmpver: 'v2c',
        community: 'secret-community',
        port: 1161,
        transport: 'tcp',
        port_association_mode: 'ifName',
    );

    $device = $creator->getDevice();
    expect($device->hostname)->toEqual('router1.example.com');
    expect($device->display_template)->toEqual('{{ $hostname }}');
    expect($device->poller_group)->toEqual(2);

    $methods = $creator->getPollingMethods($device);
    expect($methods)->toHaveCount(2);

    $icmpMethod = $methods->firstWhere('method_type', PollingMethodType::Icmp);
    expect($icmpMethod)->not->toBeNull();
    expect($icmpMethod->enabled)->toBeTrue();
    expect($icmpMethod->affects_availability)->toBeTrue();

    // same default as the other ways of adding a device
    $snmpMethod = $methods->firstWhere('method_type', PollingMethodType::Snmp);
    expect($snmpMethod)->not->toBeNull();
    expect($snmpMethod->enabled)->toBeTrue();
    expect($snmpMethod->affects_availability)->toBeTrue();
    expect($snmpMethod->settings['port'])->toEqual(1161);
    expect($snmpMethod->settings['transport'])->toEqual('tcp');
    expect($snmpMethod->settings['port_association_mode'])->toEqual('ifName');

    expect($snmpMethod->settings['version'])->toBe('v2c');

    // a given version is not tried as v1
    $secret = $snmpMethod->secret;
    expect($secret)->not->toBeNull();
    expect($secret->secret_type)->toBe(SecretType::SnmpCommunity);
    expect($secret->data)->toBe(['community' => 'secret-community']);
});

test('creates device and polling methods for v3', function (): void {
    $creator = new LegacyDeviceCreator(
        hostname: 'switch1.example.com',
        snmpver: 'v3',
        authname: 'v3user',
        authpass: 'authPassword123',
        authalgo: 'SHA-256',
        cryptopass: 'privPassword123',
        cryptoalgo: 'AES-256-CFB',
        authlevel: 'authPriv',
    );

    $device = $creator->getDevice();
    $methods = $creator->getPollingMethods($device);

    $snmpMethod = $methods->firstWhere('method_type', PollingMethodType::Snmp);
    expect($snmpMethod)->not->toBeNull();
    $secret = $snmpMethod->secret;
    expect($secret)->not->toBeNull();
    expect($secret->secret_type)->toBe(SecretType::SnmpV3);
    $this->assertArrayNotHasKey('version', $secret->data);
    expect($secret->data['authname'])->toEqual('v3user');
    expect($secret->data['authpass'])->toEqual('authPassword123');
    expect($secret->data['authalgo'])->toEqual('SHA-256');
    expect($secret->data['cryptopass'])->toEqual('privPassword123');
    expect($secret->data['cryptoalgo'])->toEqual('AES-256-CFB');
    expect($secret->data['authlevel'])->toEqual('authPriv');
});

test('without credentials the defaults are used', function (): void {
    $creator = new LegacyDeviceCreator(hostname: 'defaults.example.com');

    $snmpMethod = $creator->getPollingMethods($creator->getDevice())->firstWhere('method_type', PollingMethodType::Snmp);
    expect($snmpMethod)->not->toBeNull();
    expect($snmpMethod->secret)->toBeNull();
});

test('explicit credentials matching the defaults are kept', function (): void {
    $creator = new LegacyDeviceCreator(hostname: 'explicit.example.com', snmpver: 'v3', authname: 'root', authlevel: 'noAuthNoPriv', authalgo: 'MD5', cryptoalgo: 'AES');

    $secret = $creator->getPollingMethods($creator->getDevice())->firstWhere('method_type', PollingMethodType::Snmp)->secret;
    expect($secret)->not->toBeNull();
    expect($secret->data)->toEqual(['authlevel' => 'noAuthNoPriv', 'authname' => 'root', 'authalgo' => 'MD5', 'cryptoalgo' => 'AES']);
});

test('explicit auth level is credentials', function (): void {
    $creator = new LegacyDeviceCreator(hostname: 'authlevel.example.com', snmpver: 'v3', authlevel: 'noAuthNoPriv');

    $secret = $creator->getPollingMethods($creator->getDevice())->firstWhere('method_type', PollingMethodType::Snmp)->secret;
    expect($secret)->not->toBeNull();
    expect($secret->data['authlevel'])->toBe('noAuthNoPriv');
    expect($secret->data['authname'])->toBe('root');
    // default
});

test('only a version tries the default credentials for that version', function (): void {
    $v3 = snmpSecret(['authlevel' => 'noAuthNoPriv', 'authname' => 'user'], SecretType::SnmpV3);
    $first = snmpSecret(['community' => 'first']);
    $second = snmpSecret(['community' => 'second']);
    $third = snmpSecret(['community' => 'third']);
    LibrenmsConfig::set('snmp.default_credentials', [$v3->id, $first->id, $third->id, $second->id]);

    $probed = [];
    $backend = Mockery::mock(SnmpBackendInterface::class);
    $backend->shouldReceive('get')->andReturnUsing(function ($target, $oids, SnmpConfig $config) use (&$probed) {
        $probed[] = "$config->version $config->community";

        return $config->community === 'second'
            ? new RawSnmpResponse('SNMPv2-MIB::sysObjectID.0 = OID: SNMPv2-SMI::enterprises.9.1.1', '', 0)
            : new RawSnmpResponse('', 'Timeout: No Response', 1);
    });

    $creator = new LegacyDeviceCreator(hostname: 'v1-only.example.com', snmpver: 'v1');
    $device = $creator->getDevice();
    $snmpMethod = $creator->getPollingMethods($device)->firstWhere('method_type', PollingMethodType::Snmp);

    expect($snmpMethod->secret_id)->toBeNull();
    // only a version is not a secret
    $this->app->instance(SnmpBackendInterface::class, $backend);
    expect(app(DiscoverPollingMethod::class)->execute($device, $snmpMethod)->isSuccess())->toBeTrue();
    expect($probed)->toBe(['v1 first', 'v1 third', 'v1 second']);
    // a community is a v1 credential too, the v3 secret is skipped
    expect($snmpMethod->secret->id)->toBe($second->id);
    expect($snmpMethod->settings['version'])->toBe('v1');
});

test('v3 values without a community try the default communities', function (): void {
    // API clients send every field, the v3 defaults are not a v2c credential
    $creator = new LegacyDeviceCreator(hostname: 'v2c-defaults.example.com', snmpver: 'v2c', authlevel: 'noAuthNoPriv', authalgo: 'MD5');
    $snmpMethod = $creator->getPollingMethods($creator->getDevice())->firstWhere('method_type', PollingMethodType::Snmp);

    expect($snmpMethod->secret)->toBeNull();
    expect($snmpMethod->defaultSecretType)->toBe(SecretType::SnmpCommunity);
    expect($snmpMethod->settings['version'])->toBe('v2c');
});

test('forced add with only a version uses the first default for that version', function (): void {
    $v2c = snmpSecret(['community' => 'first']);
    $v3 = snmpSecret(['authlevel' => 'authNoPriv', 'authname' => 'user', 'authpass' => 'password1'], SecretType::SnmpV3);
    LibrenmsConfig::set('snmp.default_credentials', [$v2c->id, $v3->id]);

    expect(new LegacyDeviceCreator(hostname: 'forced-v3.example.com', snmpver: 'v3', force: true)->execute())->toBeTrue();

    $device = Device::where('hostname', 'forced-v3.example.com')->firstOrFail();
    expect($device->pollingMethod(PollingMethodType::Snmp)->secret_id)->toBe($v3->id);
});

test('ping only omits snmp method', function (): void {
    $creator = new LegacyDeviceCreator(
        hostname: 'ping-host.example.com',
        ping_only: true,
        os: 'ping',
        hardware: 'Generic Ping',
        sysName: 'ping-host',
    );

    $device = $creator->getDevice();
    expect($device->os)->toEqual('ping');
    expect($device->hardware)->toEqual('Generic Ping');
    expect($device->sysName)->toEqual('ping-host');

    $methods = $creator->getPollingMethods($device);
    expect($methods)->toHaveCount(1);
    expect($methods->firstWhere('method_type', PollingMethodType::Icmp))->not->toBeNull();
    expect($methods->firstWhere('method_type', PollingMethodType::Snmp))->toBeNull();
});

test('icmp check disabled omits icmp method', function (): void {
    LibrenmsConfig::set('icmp_check', false);

    $creator = new LegacyDeviceCreator(hostname: 'no-icmp.example.com', snmpver: 'v2c', community: 'public');
    $methods = $creator->getPollingMethods($creator->getDevice());
    expect($methods->firstWhere('method_type', PollingMethodType::Icmp))->toBeNull();
    expect($methods->firstWhere('method_type', PollingMethodType::Snmp))->not->toBeNull();

    // a ping only device is still pinged
    $creator = new LegacyDeviceCreator(hostname: 'ping-host.example.com', ping_only: true);
    $methods = $creator->getPollingMethods($creator->getDevice());
    expect($methods)->toHaveCount(1);
    expect($methods->firstWhere('method_type', PollingMethodType::Icmp))->not->toBeNull();
});

/**
 * @param  array<string, mixed>  $data
 */
function snmpSecret(array $data, SecretType $type = SecretType::SnmpCommunity): Secret
{
    return Secret::create([
        'description' => 'Default ' . implode(' ', $data),
        'secret_type' => $type,
        'data' => $data,
    ]);
}