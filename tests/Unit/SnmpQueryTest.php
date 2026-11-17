<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use \Mockery\MockInterface;
use App\Models\DevicePollingMethod;
use App\Models\Secret;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use LibreNMS\Data\Source\Snmp\SnmpBackendInterface;
use LibreNMS\Data\Source\Snmp\SnmpQueryBuilder;
use LibreNMS\Data\Source\Snmp\SnmpQueryOptions;
use LibreNMS\Data\Source\Snmp\SnmpResponse;
use LibreNMS\Data\Source\Snmp\SnmpTranslatorInterface;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Enum\SnmpOidOutput;
use LibreNMS\Enum\SnmpQuickPrint;
use LibreNMS\Polling\Method\Config\SnmpConfig;

beforeEach(function () {
    $this->device = makeDeviceWithSnmpConfig();
});

function makeDeviceWithSnmpConfig(array $secretData = [], array $settings = [], array $deviceAttrs = [], \LibreNMS\Enum\SecretType $secretType = \LibreNMS\Enum\SecretType::SnmpCommunity): Device
{
    $device = new Device(array_merge(['hostname' => '10.1.2.3'], $deviceAttrs));
    $device->device_id = 1;
    $device->setRelation('attribs', new Collection);

    $secret = new Secret([
        'secret_type' => $secretType,
        'data' => $secretType === \LibreNMS\Enum\SecretType::SnmpV3 ? $secretData : array_merge(['community' => 'test-community'], $secretData),
    ]);
    $method = new DevicePollingMethod([
        'method_type' => PollingMethodType::Snmp,
        'enabled' => true,
        'affects_availability' => true,
        'settings' => array_merge(['timeout' => 2, 'retries' => 1], $settings),
    ]);
    $method->setRelation('device', $device);
    $method->setRelation('secret', $secret);
    $device->setRelation('pollingMethods', collect([$method]));

    return $device;
}

function mockBackend(): Mockery\MockInterface&SnmpBackendInterface
{
    return Mockery::mock(SnmpBackendInterface::class);
}

test('get delegates to backend', function () {
    $mockBackend = mockBackend();
    $mockBackend->shouldReceive('get')
        ->once()
        ->withArgs(fn (string $target, array $oids, SnmpConfig $config, SnmpQueryOptions $options) => $target === '10.1.2.3'
            && $config->community === 'test-community'
            && $oids === ['sysDescr.0']
            && $options->oidFormat === SnmpOidOutput::Numeric
            && $options->context === '')
        ->andReturn(new SnmpResponse(['sysDescr.0' => 'Linux 6.0']));

    $query = new SnmpQueryBuilder($mockBackend)
        ->device($this->device)
        ->numeric();

    $response = $query->get('sysDescr.0');

    expect($response->values())->toBe(['sysDescr.0' => 'Linux 6.0']);
});

test('walk delegates for each oid', function () {
    $mockBackend = mockBackend();
    $mockBackend->shouldReceive('walk')
        ->once()
        ->withArgs(fn (string $target, string $oid) => $oid === 'ifDescr')
        ->andReturn(new SnmpResponse(['ifDescr.1' => 'eth0']));

    $mockBackend->shouldReceive('walk')
        ->once()
        ->withArgs(fn (string $target, string $oid) => $oid === 'ifType')
        ->andReturn(new SnmpResponse(['ifType.1' => 'ethernetCsmacd']));

    $query = new SnmpQueryBuilder($mockBackend)->device($this->device);
    $response = $query->walk(['ifDescr', 'ifType']);

    expect($response->values())->toBe(['ifDescr.1' => 'eth0', 'ifType.1' => 'ethernetCsmacd']);
});

test('next delegates to backend', function () {
    $mockBackend = mockBackend();
    $mockBackend->shouldReceive('next')
        ->once()
        ->withArgs(fn (string $target, array $oids) => $oids === ['sysDescr.0'])
        ->andReturn(new SnmpResponse(['sysObjectID.0' => '.1.3.6.1.4.1']));

    $query = new SnmpQueryBuilder($mockBackend)->device($this->device);
    $response = $query->next('sysDescr.0');

    expect($response->values())->toBe(['sysObjectID.0' => '.1.3.6.1.4.1']);
});

test('limit oids chunks requests', function () {
    $device = makeDeviceWithSnmpConfig(settings: ['max_oid' => 2]);

    $mockBackend = mockBackend();
    $mockBackend->shouldReceive('get')
        ->once()
        ->withArgs(fn (string $target, array $oids) => $oids === ['oid1', 'oid2'])
        ->andReturn(new SnmpResponse(['oid1' => '1', 'oid2' => '2']));

    $mockBackend->shouldReceive('get')
        ->once()
        ->withArgs(fn (string $target, array $oids) => $oids === ['oid3'])
        ->andReturn(new SnmpResponse(['oid3' => '3']));

    $query = new SnmpQueryBuilder($mockBackend)->device($device);
    $response = $query->get(['oid1', 'oid2', 'oid3']);

    expect($response->values())->toBe(['oid1' => '1', 'oid2' => '2', 'oid3' => '3']);
});

test('abort on failure', function () {
    $mockBackend = mockBackend();

    // First OID fails (exitCode 1)
    $mockBackend->shouldReceive('walk')
        ->once()
        ->withArgs(fn (string $target, string $oid) => $oid === 'unsupportedOid')
        ->andReturn(new SnmpResponse([], 'Timeout: No Response', 1));

    // Second OID should never be queried because of abortOnFailure
    $mockBackend->shouldNotReceive('walk')
        ->withArgs(fn (string $target, string $oid) => $oid === 'secondOid');

    $query = new SnmpQueryBuilder($mockBackend)
        ->device($this->device)
        ->abortOnFailure();

    $response = $query->walk(['unsupportedOid', 'secondOid']);

    expect($response->isValid())->toBeFalse();
});

test('cache prevents duplicate backend call', function () {
    Cache::flush();

    $mockBackend = mockBackend();
    $mockBackend->shouldReceive('get')
        ->once()
        ->withArgs(fn (string $target, array $oids) => $oids === ['sysDescr.0'])
        ->andReturn(new SnmpResponse(['sysDescr.0' => 'CachedLinux']));

    $query = new SnmpQueryBuilder($mockBackend)
        ->device($this->device)
        ->cache();

    $first = $query->get('sysDescr.0');
    $second = $query->get('sysDescr.0');

    expect($first->values())->toBe(['sysDescr.0' => 'CachedLinux']);
    expect($second->values())->toBe(['sysDescr.0' => 'CachedLinux']);
});

test('translate delegates to translate backend', function () {
    $mockBackend = mockBackend();
    $mockTranslate = Mockery::mock(SnmpTranslatorInterface::class);
    $mockTranslate->shouldReceive('translate')
        ->once()
        ->withArgs(fn (string $oid, SnmpQueryOptions $options) => $oid === 'IF-MIB::ifTable')
        ->andReturn('.1.3.6.1.2.1.2.2');

    $query = new SnmpQueryBuilder($mockBackend, $mockTranslate)
        ->device($this->device)
        ->numeric();

    $result = $query->translate('IF-MIB::ifTable');
    expect($result)->toBe('.1.3.6.1.2.1.2.2');
});

test('context v3 prefix', function () {
    $v3Device = makeDeviceWithSnmpConfig(secretData: [
        'authlevel' => 'noAuthNoPriv',
        'authname' => null,
    ], secretType: \LibreNMS\Enum\SecretType::SnmpV3, deviceAttrs: ['device_id' => 2]);

    $mockBackend = mockBackend();
    $mockBackend->shouldReceive('get')
        ->once()
        ->withArgs(fn (string $target, array $oids, SnmpConfig $config, SnmpQueryOptions $options) => $options->context === 'vlan-100')
        ->andReturn(new SnmpResponse(['val' => '1']));

    $query = new SnmpQueryBuilder($mockBackend)
        ->device($v3Device)
        ->context('100', 'vlan-');

    $query->get('val');
});

test('options converts net snmp flags to query options', function () {
    $mockBackend = mockBackend();
    $mockBackend->shouldReceive('walk')
        ->once()
        ->withArgs(fn (string $target, string $oid, SnmpConfig $config, SnmpQueryOptions $options) => $options->numericIndexes === true
            && $options->oidFormat === SnmpOidOutput::Suffix
            && $options->numericEnums === false
            && $options->tolerateUnorderedIndexes === true)
        ->andReturn(new SnmpResponse(['test' => '1']));

    $query = new SnmpQueryBuilder($mockBackend)
        ->device($this->device)
        ->options(['-OQUsb', '-Cc']);

    $response = $query->walk('test');
    expect($response->values())->toBe(['test' => '1']);
});

test('display hints can be disabled', function () {
    $mockBackend = mockBackend();
    $mockBackend->shouldReceive('walk')
        ->once()
        ->withArgs(fn (string $target, string $oid, SnmpConfig $config, SnmpQueryOptions $options) => $options->applyDisplayHints === false
            && $options->allowUnderscores === true)
        ->andReturn(new SnmpResponse(['test' => '793']));

    $query = new SnmpQueryBuilder($mockBackend)
        ->device($this->device)
        ->displayHints(false);

    expect($query->walk('test')->values())->toBe(['test' => '793']);
});

test('snmp query dispatches snmp query executed event', function () {
    \Illuminate\Support\Facades\Event::fake([\App\Events\SnmpQueryExecuted::class]);

    $mockBackend = Mockery::mock(SnmpBackendInterface::class);
    $mockBackend->shouldReceive('get')
        ->once()
        ->andReturn(new SnmpResponse(['sysDescr.0' => 'Linux 6.0'], command: ['/usr/bin/snmpget', 'sysDescr.0']));

    $query = new SnmpQueryBuilder($mockBackend)->device($this->device);
    $query->get('sysDescr.0');

    \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\SnmpQueryExecuted::class, fn (\App\Events\SnmpQueryExecuted $event) => $event->target === $this->device->pollerTarget()
        && $event->method === 'snmpget'
        && $event->oids === ['sysDescr.0']
        && $event->duration >= 0
        && $event->device === $this->device
        && $event->response->command === ['/usr/bin/snmpget', 'sysDescr.0']
        && $event->response->values() === ['sysDescr.0' => 'Linux 6.0']);
});

test('snmp query defaults to quick print options', function () {
    $mockBackend = mockBackend();
    $mockBackend->shouldReceive('get')
        ->once()
        ->withArgs(fn (string $target, array $oids, SnmpConfig $config, SnmpQueryOptions $options) => $options->quickPrint === SnmpQuickPrint::Equals
                && $options->extendedIndex === true
                && $options->printUnits === false
                && $options->numericEnums === true
                && $options->numericTimeticks === true)
        ->andReturn(new SnmpResponse(['sysDescr.0' => 'Linux']));

    $query = new SnmpQueryBuilder($mockBackend)->device($this->device);
    $query->get('sysDescr.0');
});

test('hide mib sets suffix oid format', function () {
    $mockBackend = mockBackend();
    $mockBackend->shouldReceive('get')
        ->once()
        ->withArgs(fn (string $target, array $oids, SnmpConfig $config, SnmpQueryOptions $options) => $options->oidFormat === SnmpOidOutput::Suffix)
        ->andReturn(new SnmpResponse(['sysDescr.0' => 'Linux']));

    $query = new SnmpQueryBuilder($mockBackend)->device($this->device)->hideMib();
    $query->get('sysDescr.0');
});

test('prepare options disables bulk for no bulk oids', function () {
    $this->device->os = 'generic';
    \App\Facades\LibrenmsConfig::set('os.generic.oids.no_bulk', ['UCD-SNMP-MIB::laLoadInt']);

    $mockBackend = mockBackend();
    $mockBackend->shouldReceive('walk')
        ->once()
        ->withArgs(fn (string $target, string $oid, SnmpConfig $config, SnmpQueryOptions $options) => $options->allowBulk === false)
        ->andReturn(new SnmpResponse(['laLoadInt' => '1']));

    $query = new SnmpQueryBuilder($mockBackend)->device($this->device);
    $query->walk('UCD-SNMP-MIB::laLoadInt');
});