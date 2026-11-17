<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\DeviceAttrib;
use App\Models\DevicePollingMethod;
use App\Models\Secret;
use Illuminate\Support\Collection;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Enum\SecretType;
use LibreNMS\Polling\Method\Config\IpmiConfig;
use LibreNMS\Polling\Method\Config\PollingMethodConfig;
use LibreNMS\Polling\Method\Config\UnixAgentConfig;
use LibreNMS\Polling\Method\Methods\IcmpPollingMethod;
use LibreNMS\Polling\Method\Methods\IpmiPollingMethod;
use LibreNMS\Polling\Method\Methods\PollingMethod;
use LibreNMS\Polling\Method\Methods\ProxmoxApiPollingMethod;
use LibreNMS\Polling\Method\Methods\SnmpPollingMethod;
use LibreNMS\Polling\Method\Methods\UnixAgentPollingMethod;
use LibreNMS\Polling\Method\PollingMethodRegistry;

beforeEach(function () {
    config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);
    // secret data is encrypted
});

test('unset settings use the configured defaults', function () {
    LibrenmsConfig::set('snmp.transports', ['tcp', 'udp']);
    LibrenmsConfig::set('snmp.port', 1161);
    LibrenmsConfig::set('snmp.timeout', 2.5);
    LibrenmsConfig::set('snmp.retries', 3);
    LibrenmsConfig::set('unix-agent.port', 6557);
    LibrenmsConfig::set('unix-agent.connection-timeout', 7);
    $device = new Device(['hostname' => 'example.com']);

    $snmp = config(new SnmpPollingMethod, deviceMethod(PollingMethodType::Snmp, device: $device));
    expect($snmp->transport)->toBe('tcp');
    expect($snmp->port)->toBe(1161);
    expect($snmp->timeout)->toBe(2.5);
    expect($snmp->retries)->toBe(3);

    $unixAgent = config($device);
    expect($unixAgent->port)->toBe(6557);
    expect($unixAgent->timeout)->toBe(7);

    expect(config($device)->ipVersion)->toBe('default');
});

test('stored settings override defaults', function () {
    $method = new SnmpPollingMethod;
    $config = config($method, deviceMethod(PollingMethodType::Snmp, ['transport' => 'tcp', 'max_oid' => 20]));

    expect($config->transport)->toBe('tcp');
    expect($config->maxOid)->toBe(20);
    expect($config->port)->toBe($method->defaults()['port']);

    expect(config(new IcmpPollingMethod, deviceMethod(PollingMethodType::Icmp, ['ip_version' => 'ipv6']))->ipVersion)->toBe('ipv6');
    expect(config(new UnixAgentPollingMethod, deviceMethod(PollingMethodType::UnixAgent, ['port' => 6557]))->port)->toBe(6557);
});

test('secret is filled into config', function () {
    $v2c = deviceMethod(PollingMethodType::Snmp, ['port' => 1161], ['community' => 'shared']);
    $v1 = deviceMethod(PollingMethodType::Snmp, ['version' => 'v1'], ['community' => 'shared']);
    $v3 = deviceMethod(PollingMethodType::Snmp, [], [
        'authlevel' => 'authPriv',
        'authname' => 'user1',
        'authpass' => 'pass12345',
        'cryptopass' => 'crypt12345',
    ]);

    $method = new SnmpPollingMethod;
    $v2cConfig = config($method, $v2c);
    expect($v2cConfig->version)->toBe('v2c');
    expect($v2cConfig->community)->toBe('shared');
    expect($v2cConfig->port)->toBe(1161);

    $v1Config = config($method, $v1);
    expect($v1Config->version)->toBe('v1');
    // a community may be used as v1
    expect($v1Config->community)->toBe('shared');

    $v3Config = config($method, $v3);
    expect($v3Config->version)->toBe('v3');
    expect($v3Config->community)->toBeNull();
    expect($v3Config->authname)->toBe('user1');
    expect($v3Config->cryptoalgo)->toBe('AES');
    // secret data default
});

test('snmp defaults follow device os', function () {
    LibrenmsConfig::set('os.test-os.snmp.max_repeaters', 3);
    $device = new Device(['os' => 'test-os']);

    expect((new SnmpPollingMethod)->defaults($device)['max_repeaters'])->toBe(3);

    // nothing stored, so the OS default is used rather than pinned
    expect(config(new SnmpPollingMethod, deviceMethod(PollingMethodType::Snmp, device: $device))->maxRepeaters)->toBe(3);
});

test('snmp bulk setting is used except for v1', function () {
    $method = new SnmpPollingMethod;
    $community = ['community' => 'shared'];
    $v3 = ['authlevel' => 'noAuthNoPriv', 'authname' => 'user1'];

    expect(config($method, deviceMethod(PollingMethodType::Snmp, [], $community))->bulk)->toBeTrue();
    expect(config($method, deviceMethod(PollingMethodType::Snmp, ['bulk' => false], $community))->bulk)->toBeFalse();
    expect(config($method, deviceMethod(PollingMethodType::Snmp, ['bulk' => false], $v3))->bulk)->toBeFalse();

    // v1 has no GETBULK, whatever the setting or OS default
    expect(config($method, deviceMethod(PollingMethodType::Snmp, ['version' => 'v1'], $community))->bulk)->toBeFalse();
    expect(config($method, deviceMethod(PollingMethodType::Snmp, ['version' => 'v1', 'bulk' => true], $community))->bulk)->toBeFalse();

    // the version setting only applies to a community, so v3 keeps bulk
    expect(config($method, deviceMethod(PollingMethodType::Snmp, ['version' => 'v1'], $v3))->bulk)->toBeTrue();
});

test('snmp bulk default follows os setting', function () {
    LibrenmsConfig::set('os.test-os.snmp_bulk', false);
    $device = new Device(['hostname' => 'example.com', 'os' => 'test-os']);
    $method = new SnmpPollingMethod;

    expect($method->defaults($device)['bulk'])->toBeFalse();
    expect(config($method, deviceMethod(PollingMethodType::Snmp, device: $device))->bulk)->toBeFalse();
    expect(config($method, deviceMethod(PollingMethodType::Snmp, ['bulk' => true], device: $device))->bulk)->toBeTrue();
    // stored setting wins
});

test('snmp transport default follows config', function () {
    LibrenmsConfig::set('snmp.transports.0', 'tcp6');

    expect(config(new SnmpPollingMethod, deviceMethod(PollingMethodType::Snmp))->transport)->toBe('tcp6');
});

test('ipmi hostname defaults to device hostname', function () {
    $device = new Device(['hostname' => 'switch.example.com']);
    $method = new IpmiPollingMethod;

    $config = config($method, deviceMethod(PollingMethodType::Ipmi, device: $device));
    expect($config->hostname)->toBe('switch.example.com');
    expect($config->port)->toBeNull();
    // left to ipmitool
    expect($config->timeout)->toBeNull();
    expect($config->type)->toBe('');

    // detected
    $config = config($method, deviceMethod(PollingMethodType::Ipmi, [
        'hostname' => 'ipmi.example.com',
        'port' => 6230,
        'ciphersuite' => 3,
        'type' => 'lanplus',
    ], device: $device));
    expect($config->hostname)->toBe('ipmi.example.com');
    expect($config->port)->toBe(6230);
    expect($config->ciphersuite)->toBe(3);
    expect($config->type)->toBe('lanplus');
});

test('stored bool settings select their form option', function () {
    $definition = (new ProxmoxApiPollingMethod)->definition();

    foreach (['1' => true, '0' => false] as $option => $stored) {
        $values = $definition->formValues(['verify_ssl' => $stored]);

        expect($values['verify_ssl'])->toBe((string) $option);
        expect($definition->filterOverrides($values)['verify_ssl'])->toBe($stored);
    }

    expect($definition->formValues([])['verify_ssl'])->toBe('');
    // the default
});

test('proxmox defaults and token', function () {
    $device = new Device(['hostname' => 'pve.example.com']);
    $method = new ProxmoxApiPollingMethod;

    $config = config($method, deviceMethod(PollingMethodType::ProxmoxApi, device: $device));
    expect($config->hostname)->toBe('pve.example.com');
    expect($config->port)->toBe(8006);
    expect($config->timeout)->toBe(10);
    expect($config->verifySsl)->toBeTrue();
    expect($config->tokenId)->toBe('');

    $config = config($method, deviceMethod(PollingMethodType::ProxmoxApi, [
        'hostname' => '192.0.2.10',
        'port' => 443,
        'verify_ssl' => true,
    ], [
        'token_id' => 'librenms@pve!monitoring',
        'token_secret' => 'abc123',
    ], $device));
    expect($config->hostname)->toBe('192.0.2.10');
    expect($config->port)->toBe(443);
    expect($config->verifySsl)->toBeTrue();
    expect($config->tokenId)->toBe('librenms@pve!monitoring');
    expect($config->tokenSecret)->toBe('abc123');
});

test('device uses configured method or default', function () {
    $icmp = deviceMethod(PollingMethodType::Icmp, ['ip_version' => 'ipv6']);
    $device = new Device(['hostname' => '192.0.2.1']);
    $device->setRelation('pollingMethods', new Collection([$icmp]));

    expect($device->polling()->isEnabled(PollingMethodType::Icmp))->toBeTrue();
    expect($device->polling()->icmp()->ipVersion)->toBe('ipv6');

    expect($device->polling()->isEnabled(PollingMethodType::Ipmi))->toBeFalse();
    $ipmi = $device->polling()->get(PollingMethodType::Ipmi);
    expect($ipmi)->toBeInstanceOf(IpmiConfig::class);
    expect($ipmi->hostname)->toBe('192.0.2.1');
    $agent = $device->polling()->get(PollingMethodType::UnixAgent);
    expect($agent)->toBeInstanceOf(UnixAgentConfig::class);
    expect($agent->port)->toBe(6556);
});

test('snmp without polling methods uses the defaults', function () {
    LibrenmsConfig::set('os.test-os.snmp.max_repeaters', 10);

    $device = new Device(['hostname' => '192.0.2.1', 'os' => 'test-os']);
    $device->setAttribute('community', 'legacy-community');
    $device->setAttribute('port', 1161);
    $device->setRelation('pollingMethods', new Collection);
    $device->setRelation('attribs', new Collection([
        new DeviceAttrib(['attrib_type' => 'snmp_max_oid', 'attrib_value' => '20']),
    ]));

    // legacy device fields and attribs are not used
    $config = $device->polling()->snmp();
    expect($config->community)->toBeNull();
    expect($config->port)->toBe((int) LibrenmsConfig::get('snmp.port'));
    expect($config->maxOid)->toBe((int) LibrenmsConfig::get('snmp.max_oid'));

    // os defaults still apply
    expect($config->maxRepeaters)->toBe(10);
});

/**
 * @template T of PollingMethodConfig
 *
 * @param  PollingMethod<T>  $method
 * @return T
 */
function config(PollingMethod $method, DevicePollingMethod $deviceMethod): PollingMethodConfig
{
    return config($deviceMethod->device, $deviceMethod);
}

/**
 * @param  array<string, mixed>  $settings
 * @param  array<string, mixed>|null  $secretData
 */
function deviceMethod(PollingMethodType $type, array $settings = [], ?array $secretData = null, ?Device $device = null): DevicePollingMethod
{
    $deviceMethod = new DevicePollingMethod([
        'method_type' => $type,
        'enabled' => true,
        'affects_availability' => true,
        'settings' => $settings,
    ]);
    $deviceMethod->setRelation('device', $device ?? new Device(['hostname' => 'example.com']));
    $deviceMethod->setRelation('secret', $secretData === null ? null : new Secret([
        'secret_type' => match ($type) {
            PollingMethodType::Snmp => isset($secretData['community']) ? SecretType::SnmpCommunity : SecretType::SnmpV3,
            default => (new PollingMethodRegistry)->get($type)->secretTypes()[0],
        },
        'data' => $secretData,
    ]));

    return $deviceMethod;
}
