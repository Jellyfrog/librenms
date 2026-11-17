<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\DevicePollingMethod;
use App\Models\Secret;
use Illuminate\Database\Eloquent\Collection;
use LibreNMS\Data\Source\Snmp\NetSnmp;
use LibreNMS\Data\Source\Snmp\NetSnmpOptions;
use LibreNMS\Data\Source\Snmp\RawSnmpResponse;
use LibreNMS\Data\Source\Snmp\SnmpBackendInterface;
use LibreNMS\Data\Source\Snmp\SnmpQueryOptions;
use LibreNMS\Data\Source\Snmp\SnmpResponse;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Enum\SnmpOidOutput;
use LibreNMS\Enum\SnmpStringOutput;
use LibreNMS\Polling\Method\Config\SnmpConfig;
use LibreNMS\Polling\Method\Methods\SnmpPollingMethod;
use LibreNMS\Polling\Secrets\Data\SnmpCommunitySecretData;
use LibreNMS\Polling\Secrets\Data\SnmpV3SecretData;

beforeEach(function () {
    $this->backend = new NetSnmp();
    $this->optionsParser = new NetSnmpOptions();
});

function makeDeviceWithSnmpConfig(array $secretData = [], array $settings = [], array $deviceAttrs = []): Device
{
    $device = new Device(array_merge(['hostname' => 'router1.example.com'], $deviceAttrs));
    $device->device_id = 1;
    $device->setRelation('attribs', new Collection);

    $secret = new Secret([
        'secret_type' => \LibreNMS\Enum\SecretType::SnmpCommunity,
        'data' => array_merge(['community' => 'test-comm'], $secretData),
    ]);
    $method = new DevicePollingMethod([
        'method_type' => PollingMethodType::Snmp,
        'enabled' => true,
        'affects_availability' => true,
        'settings' => $settings,
    ]);
    $method->setRelation('device', $device);
    $method->setRelation('secret', $secret);
    $device->setRelation('pollingMethods', collect([$method]));

    return $device;
}

test('build cli v2c', function () {
    $config = SnmpConfig::make(
        [
            'transport' => 'udp',
            'port' => 161,
        ] + (new SnmpPollingMethod)->defaults(),
        new SnmpCommunitySecretData(
            community: 'public',
        ),
    );

    $options = SnmpQueryOptions::quickPrint();
    $cli = $this->optionsParser->buildCli('snmpget', '192.168.1.1', ['sysDescr.0'], $config, $options);

    expect($cli[0])->toEndWith('snmpget');
    expect($cli)->toContain('-M');
    expect($cli)->toContain('-m');
    expect($cli)->toContain('-v2c');
    expect($cli)->toContain('-c');
    expect($cli)->toContain('public');
    expect($cli)->toContain('-OQXUte');
    expect($cli)->toContain('udp:192.168.1.1:161');
    expect($cli)->toContain('sysDescr.0');
});

test('build cli net snmp library defaults', function () {
    $config = SnmpConfig::make(
        [
            'transport' => 'udp',
            'port' => 161,
        ] + (new SnmpPollingMethod)->defaults(),
        new SnmpCommunitySecretData(
            community: 'public',
        ),
    );

    $options = new SnmpQueryOptions();
    $cli = $this->optionsParser->buildCli('snmpget', '192.168.1.1', ['sysDescr.0'], $config, $options);

    expect($cli[0])->toEndWith('snmpget');
    expect($cli)->not->toContain('-OQXUte');
    foreach ($cli as $arg) {
        $this->assertStringStartsNotWith('-O', $arg);
    }
});

test('build cli v1', function () {
    $config = SnmpConfig::make(
        [
            'version' => 'v1',
            'transport' => 'udp',
            'port' => 161,
        ] + (new SnmpPollingMethod)->defaults(),
        new SnmpCommunitySecretData(
            community: 'secret',
        ),
    );

    $options = new SnmpQueryOptions();
    $cli = $this->optionsParser->buildCli('snmpget', '10.0.0.1', ['sysUpTime.0'], $config, $options);

    expect($cli)->toContain('-v1');
    expect($cli)->toContain('secret');
    expect($cli)->toContain('udp:10.0.0.1:161');
});

test('build cli ipv6 transport', function () {
    $build = fn (string $transport, string $target) => $this->optionsParser->buildCli('snmpget', $target, ['sysDescr.0'], SnmpConfig::make(
        ['transport' => $transport, 'port' => 161] + (new SnmpPollingMethod)->defaults(),
        new SnmpCommunitySecretData(community: 'public'),
    ), new SnmpQueryOptions());

    expect($build('udp', '2001:db8::1'))->toContain('udp6:[2001:db8::1]:161');
    expect($build('tcp', '2001:db8::1'))->toContain('tcp6:[2001:db8::1]:161');
    expect($build('udp6', '2001:db8::1'))->toContain('udp6:[2001:db8::1]:161');
    expect($build('tcp', '192.168.1.1'))->toContain('tcp:192.168.1.1:161');
    expect($build('udp', 'router1.example.com'))->toContain('udp:router1.example.com:161');
});

test('build cli v2c with context', function () {
    $config = SnmpConfig::make(
        (new SnmpPollingMethod)->defaults(),
        new SnmpCommunitySecretData(
            community: 'public',
        ),
    );

    $options = new SnmpQueryOptions(context: 'vrf1');
    $cli = $this->optionsParser->buildCli('snmpget', '192.168.1.1', ['sysDescr.0'], $config, $options);

    expect($cli)->toContain('public@vrf1');
});

test('build cli v3 auth priv', function () {
    $config = SnmpConfig::make(
        (new SnmpPollingMethod)->defaults(),
        new SnmpV3SecretData(
            authname: 'admin',
            authpass: 'auth_pass',
            authlevel: 'authPriv',
            authalgo: 'SHA',
            cryptopass: 'priv_pass',
            cryptoalgo: 'AES',
        ),
    );

    $options = new SnmpQueryOptions(context: 'ctx');
    $cli = $this->optionsParser->buildCli('snmpget', '192.168.1.5', ['sysDescr.0'], $config, $options);

    expect($cli)->toContain('-v3');
    expect($cli)->toContain('-l');
    expect($cli)->toContain('authPriv');
    expect($cli)->toContain('-n');
    expect($cli)->toContain('ctx');
    expect($cli)->toContain('-a');
    expect($cli)->toContain('SHA');
    expect($cli)->toContain('-A');
    expect($cli)->toContain('auth_pass');
    expect($cli)->toContain('-x');
    expect($cli)->toContain('AES');
    expect($cli)->toContain('-X');
    expect($cli)->toContain('priv_pass');
    expect($cli)->toContain('-u');
    expect($cli)->toContain('admin');
});

test('build cli v3 auth no priv', function () {
    $config = SnmpConfig::make(
        (new SnmpPollingMethod)->defaults(),
        new SnmpV3SecretData(
            authname: 'user1',
            authpass: 'auth_pass',
            authlevel: 'authNoPriv',
            authalgo: 'MD5',
        ),
    );

    $options = new SnmpQueryOptions();
    $cli = $this->optionsParser->buildCli('snmpget', '192.168.1.5', ['sysDescr.0'], $config, $options);

    expect($cli)->toContain('-v3');
    expect($cli)->toContain('-l');
    expect($cli)->toContain('authNoPriv');
    expect($cli)->toContain('-a');
    expect($cli)->toContain('MD5');
    expect($cli)->toContain('-A');
    expect($cli)->toContain('auth_pass');
    expect($cli)->not->toContain('-x');
    expect($cli)->not->toContain('-X');
    expect($cli)->toContain('-u');
    expect($cli)->toContain('user1');
});

test('build cli v3 no auth no priv', function () {
    $config = SnmpConfig::make(
        (new SnmpPollingMethod)->defaults(),
        new SnmpV3SecretData(
            authname: 'guest',
            authlevel: 'noAuthNoPriv',
        ),
    );

    $options = new SnmpQueryOptions();
    $cli = $this->optionsParser->buildCli('snmpget', '192.168.1.5', ['sysDescr.0'], $config, $options);

    expect($cli)->toContain('-v3');
    expect($cli)->toContain('-l');
    expect($cli)->toContain('noAuthNoPriv');
    expect($cli)->not->toContain('-a');
    expect($cli)->not->toContain('-x');
    expect($cli)->toContain('-u');
    expect($cli)->toContain('guest');
});

test('build cli ipv6 brackets', function () {
    $config = SnmpConfig::make(
        [
            'transport' => 'udp6',
            'port' => 161,
        ] + (new SnmpPollingMethod)->defaults(),
        new SnmpCommunitySecretData(
            community: 'public',
        ),
    );

    $cli = $this->optionsParser->buildCli('snmpget', '2001:db8::1', ['sysDescr.0'], $config, new SnmpQueryOptions());

    expect($cli)->toContain('udp6:[2001:db8::1]:161');
});

test('build cli timeout and retries', function () {
    $config = SnmpConfig::make(
        [
            'timeout' => 3,
            'retries' => 0,
        ] + (new SnmpPollingMethod)->defaults(),
        new SnmpCommunitySecretData(
            community: 'public',
        ),
    );

    $cli = $this->optionsParser->buildCli('snmpget', '192.168.1.1', ['sysDescr.0'], $config, new SnmpQueryOptions());

    expect($cli)->toContain('-t');
    expect($cli)->toContain('3');
    expect($cli)->toContain('-r');
    expect($cli)->toContain('0');
});

test('build cli float timeout', function () {
    $config = SnmpConfig::make(
        [
            'timeout' => 0.2,
        ] + (new SnmpPollingMethod)->defaults(),
        new SnmpCommunitySecretData(
            community: 'public',
        ),
    );

    $cli = $this->optionsParser->buildCli('snmpget', '192.168.1.1', ['sysDescr.0'], $config, new SnmpQueryOptions());

    expect($cli)->toContain('-t');
    expect($cli)->toContain('0.2');
});

test('build cli zero or negative timeout does not emit flag', function () {
    $config = SnmpConfig::make(
        [
            'timeout' => 0,
        ] + (new SnmpPollingMethod)->defaults(),
        new SnmpCommunitySecretData(
            community: 'public',
        ),
    );

    $cli = $this->optionsParser->buildCli('snmpget', '192.168.1.1', ['sysDescr.0'], $config, new SnmpQueryOptions());

    expect($cli)->not->toContain('-t');
});

test('build cli decides bulk', function () {
    $configV2 = SnmpConfig::make(
        (new SnmpPollingMethod)->defaults(),
        new SnmpCommunitySecretData(
            community: 'public',
        ),
    );
    $configV1 = SnmpConfig::make(
        ['version' => 'v1'] + (new SnmpPollingMethod)->defaults(),
        new SnmpCommunitySecretData(
            community: 'public',
        ),
    );

    // v2c with allowBulk (default) upgrades snmpwalk to snmpbulkwalk
    $cliBulk = $this->optionsParser->buildCli('snmpwalk', '192.168.1.1', ['.'], $configV2, new SnmpQueryOptions());
    expect($cliBulk[0])->toEndWith('snmpbulkwalk');

    // v2c with allowBulk: false stays snmpwalk
    $cliNoBulk = $this->optionsParser->buildCli('snmpwalk', '192.168.1.1', ['.'], $configV2, new SnmpQueryOptions(allowBulk: false));
    expect($cliNoBulk[0])->toEndWith('snmpwalk');

    // v1 with allowBulk: true stays snmpwalk because v1 does not support bulk
    $cliV1 = $this->optionsParser->buildCli('snmpwalk', '192.168.1.1', ['.'], $configV1, new SnmpQueryOptions(allowBulk: true));
    expect($cliV1[0])->toEndWith('snmpwalk');

    // v2c with bulk: false on SnmpConfig stays snmpwalk even with allowBulk: true
    $configNoBulk = SnmpConfig::make(
        [
            'bulk' => false,
        ] + (new SnmpPollingMethod)->defaults(),
        new SnmpCommunitySecretData(
            community: 'public',
        ),
    );
    $cliTargetNoBulk = $this->optionsParser->buildCli('snmpwalk', '192.168.1.1', ['.'], $configNoBulk, new SnmpQueryOptions(allowBulk: true));
    expect($cliTargetNoBulk[0])->toEndWith('snmpwalk');
});

test('build cli formatting options', function () {
    $config = SnmpConfig::make(
        (new SnmpPollingMethod)->defaults(),
        new SnmpCommunitySecretData(
            community: 'public',
        ),
    );

    $options = SnmpQueryOptions::quickPrint();
    $options->tolerateUnorderedIndexes = true;
    $options->oidFormat = SnmpOidOutput::Numeric;
    $options->numericIndexes = true;
    $options->numericEnums = false;

    $cli = $this->optionsParser->buildCli('snmpget', '192.168.1.1', ['sysDescr.0'], $config, $options);

    expect($cli)->toContain('-OQXUtbn');
    // numeric suppression of 's', no 'e' because numericEnums = false
    expect($cli)->toContain('-Cc');

    // When oidFormat is Suffix, 's' is emitted
    $optionsSymbolic = SnmpQueryOptions::quickPrint();
    $optionsSymbolic->oidFormat = SnmpOidOutput::Suffix;
    $cliSymbolic = $this->optionsParser->buildCli('snmpget', '192.168.1.1', ['sysDescr.0'], $config, $optionsSymbolic);
    expect($cliSymbolic)->toContain('-OQXUtes');
});

test('build cli ascii strings', function () {
    $config = SnmpConfig::make(
        (new SnmpPollingMethod)->defaults(),
        new SnmpCommunitySecretData(
            community: 'public',
        ),
    );

    $options = SnmpQueryOptions::quickPrint();
    $options->stringFormat = SnmpStringOutput::Ascii;
    $cli = $this->optionsParser->buildCli('snmpget', '192.168.1.1', ['sysDescr.0'], $config, $options);
    expect($cli)->toContain('-OQXUtea');

    $optionsFromCli = $this->optionsParser->parseCli(['-OteQUSab', '-Pu', '-Ih']);
    $cliFromCli = $this->optionsParser->buildCli('snmpwalk', '192.168.1.1', ['.1.3.6.1.4.1.2356.100'], $config, $optionsFromCli);
    expect($cliFromCli)->toContain('-OQUteba');
    expect($cliFromCli)->toContain('-Pu');
    expect($cliFromCli)->toContain('-Ih');
});

test('build cli hex strings', function () {
    $config = SnmpConfig::make(
        (new SnmpPollingMethod)->defaults(),
        new SnmpCommunitySecretData(
            community: 'public',
        ),
    );

    $options = SnmpQueryOptions::quickPrint();
    $options->stringFormat = SnmpStringOutput::Hex;
    $cli = $this->optionsParser->buildCli('snmpget', '192.168.1.1', ['sysDescr.0'], $config, $options);
    expect($cli)->toContain('-OQXUtex');

    $optionsFromCli = $this->optionsParser->parseCli(['-OteQUax', '-Pu']);
    $cliFromCli = $this->optionsParser->buildCli('snmpwalk', '192.168.1.1', ['.1.3.6.1.4.1.2356.100'], $config, $optionsFromCli);
    expect($cliFromCli)->toContain('-OQUtex');
    expect($cliFromCli)->toContain('-Pu');
});

test('snmp config from device', function () {
    $device = makeDeviceWithSnmpConfig(secretData: [
        'community' => 'test-comm',
    ], settings: [
        'port' => 1161,
        'timeout' => 2,
        'retries' => 3,
    ]);

    $config = $device->polling()->snmp();

    expect($config->version)->toBe('v2c');
    expect($config->community)->toBe('test-comm');
    expect($config->port)->toBe(1161);
    expect($config->timeout)->toEqual(2);
    expect($config->retries)->toBe(3);
    expect($config->bulk)->toBeTrue();
});

test('device to snmp config handles mutation', function () {
    $device = makeDeviceWithSnmpConfig(secretData: ['community' => 'test-comm']);

    $config1 = $device->polling()->snmp();

    // Mutate the polling method's secret data
    $snmpMethod = $device->pollingMethod(PollingMethodType::Snmp);
    $secret = $snmpMethod->secret;
    $secret->data = array_merge($secret->data, ['community' => 'new']);

    $config2 = $device->polling()->snmp();

    expect($config1->community)->toBe('test-comm');
    expect($config2->community)->toBe('new');
});

test('snmp config from device respects snmp bulk setting', function () {
    $deviceBulk = makeDeviceWithSnmpConfig(deviceAttrs: ['hostname' => 'bulk.device', 'os' => 'ios']);
    $configBulk = $deviceBulk->polling()->snmp();
    expect($configBulk->bulk)->toBeTrue();

    \App\Facades\LibrenmsConfig::set('os.airos.snmp_bulk', false);
    $deviceNoBulk = makeDeviceWithSnmpConfig(deviceAttrs: ['hostname' => 'nobulk.device', 'os' => 'airos']);
    $configNoBulk = $deviceNoBulk->polling()->snmp();
    expect($configNoBulk->bulk)->toBeFalse();
});

test('snmp config from device float timeout', function () {
    $device = makeDeviceWithSnmpConfig(settings: ['timeout' => 0.5]);

    $config = $device->polling()->snmp();
    expect($config->timeout)->toBe(0.5);
});

test('snmp response stores command', function () {
    $response = new SnmpResponse(['test' => '1'], '', 0, ['/usr/bin/snmpget', 'test']);
    expect($response->command)->toBe(['/usr/bin/snmpget', 'test']);

    $appended = $response->append(new SnmpResponse(['test2' => '2']));
    expect($appended->command)->toBe(['/usr/bin/snmpget', 'test']);
});

test('mib directories resolves os and group', function () {
    $dirs = \LibreNMS\Util\Mib::directories('ios', ['custom/mib/dir', 'juniper']);
    $joined = implode(':', $dirs);

    $this->assertStringContainsString('cisco', $joined);
    $this->assertStringNotContainsString('custom/mib/dir', $joined);
    $this->assertStringContainsString('juniper', $joined);
});

test('debug snmpwalk controller builds command line', function () {
    $device = makeDeviceWithSnmpConfig(secretData: ['community' => 'secret'], settings: ['port' => 161], deviceAttrs: [
        'hostname' => 'debug.device.local',
        'os' => 'ios',
    ]);

    $controller = new \App\Http\Controllers\Device\Debug\DebugSnmpwalkController();
    $refMethod = new \ReflectionMethod($controller, 'buildCommandLine');
    $cmd = $refMethod->invoke($controller, $device);

    $this->assertStringContainsString('snmp', $cmd[0]);
    expect($cmd)->toContain('udp:debug.device.local:161');
    expect($cmd)->toContain('-OUebn');
    expect($cmd)->not->toContain('-Pu');
    expect($cmd)->not->toContain('-OQXUte');
    expect($cmd)->toContain('.');
});

test('debug snmpwalk controller respects os snmp bulk false', function () {
    \App\Facades\LibrenmsConfig::set('os.airos.snmp_bulk', false);
    $device = makeDeviceWithSnmpConfig(secretData: ['community' => 'secret'], settings: ['port' => 161], deviceAttrs: [
        'hostname' => 'nobulk.device.local',
        'os' => 'airos',
    ]);

    $controller = new \App\Http\Controllers\Device\Debug\DebugSnmpwalkController();
    $refMethod = new \ReflectionMethod($controller, 'buildCommandLine');
    $cmd = $refMethod->invoke($controller, $device);

    expect($cmd[0])->toEndWith('snmpwalk');
});

test('translate already numeric oid returns immediately', function () {
    $options = new SnmpQueryOptions(oidFormat: SnmpOidOutput::Numeric);
    $result = $this->backend->translate('.1.3.6.1.2.1.1.1.0', $options);
    expect($result)->toBe('.1.3.6.1.2.1.1.1.0');

    $resultWithoutDot = $this->backend->translate('1.3.6.1.2.1.1.1.0', $options);
    expect($resultWithoutDot)->toBe('.1.3.6.1.2.1.1.1.0');
});

test('legacy oid limit uses the snmp polling method', function () {
    \App\Facades\LibrenmsConfig::set('snmp.max_oid', 5);
    \App\Facades\DeviceCache::fake(makeDeviceWithSnmpConfig(settings: ['max_oid' => 20]));

    expect(get_device_oid_limit(['device_id' => 1, 'os' => 'generic']))->toBe(20);
});

test('legacy snmp exec uses the context from the device array', function () {
    $device = makeDeviceWithSnmpConfig(settings: ['context' => 'vrf-default']);
    \App\Facades\DeviceCache::fake($device);
    $deviceArray = ['device_id' => 1, 'hostname' => 'router1.example.com', 'overwrite_ip' => null, 'os' => 'generic'];

    $contexts = [];
    $backend = \Mockery::mock(SnmpBackendInterface::class);
    $backend->shouldReceive('get')->twice()->andReturnUsing(function ($target, $oids, $config, SnmpQueryOptions $options) use (&$contexts) {
        $contexts[] = $options->context;

        return new RawSnmpResponse('SNMPv2-MIB::sysName.0 = STRING: router1', '', 0);
    });
    $this->app->instance(SnmpBackendInterface::class, $backend);

    snmp_get($deviceArray + ['context_name' => 'vrf-a'], 'SNMPv2-MIB::sysName.0', '-Oqv');
    snmp_get($deviceArray, 'SNMPv2-MIB::sysName.0', '-Oqv');

    // an ad-hoc context overrides the device default context
    expect($contexts)->toBe(['vrf-a', 'vrf-default']);
});