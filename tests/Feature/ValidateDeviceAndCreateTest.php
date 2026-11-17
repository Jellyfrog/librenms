<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Actions\Device\BuildDefaultPollingMethods;
use App\Actions\Device\ValidateDeviceAndCreate;
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\DevicePollingMethod;
use App\Models\Secret;
use Illuminate\Validation\ValidationException;
use LibreNMS\Data\Source\Icmp\Fping;
use LibreNMS\Data\Source\Icmp\FpingResponse;
use LibreNMS\Data\Source\Snmp\RawSnmpResponse;
use LibreNMS\Data\Source\Snmp\SnmpBackendInterface;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Enum\SecretType;
use LibreNMS\Exceptions\HostSysnameExistsException;
use LibreNMS\Exceptions\MissingSecretException;
use LibreNMS\Polling\Method\Methods\SnmpPollingMethod;
use PHPUnit\Framework\Attributes\DataProvider;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('os without snmp is ping', function (array $methods, ?string $os, string $expected) {
    $secret = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity]);
    LibrenmsConfig::set('snmp.default_credentials', [$secret->id]);

    // used by forced snmp
    $device = new Device(['hostname' => 'os-test.example.com', 'os' => $os]);
    $pollingMethods = app(BuildDefaultPollingMethods::class)->execute($device, ['methods' => $methods]);

    expect(resolve(ValidateDeviceAndCreate::class)->execute($device, $pollingMethods, force: true))->toBeTrue();
    expect($device->fresh()->os)->toBe($expected);
})->with('osProvider');

test('default credentials are tried in order and the working one is kept', function () {
    $wrong = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'wrong']]);
    $correct = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'correct']]);
    LibrenmsConfig::set('snmp.default_credentials', [$wrong->id, $correct->id]);

    $fping = Mockery::mock(Fping::class);
    $fping->shouldReceive('ping')->andReturn(FpingResponse::artificialUp());
    $this->app->instance(Fping::class, $fping);

    $tried = [];
    $backend = Mockery::mock(SnmpBackendInterface::class);
    $backend->shouldReceive('get')->andReturnUsing(function ($target, $oids, $config) use (&$tried) {
        $tried[] = $config->community;

        return $config->community === 'correct'
            ? new RawSnmpResponse('SNMPv2-MIB::sysObjectID.0 = OID: SNMPv2-SMI::enterprises.9.1.1', '', 0)
            : new RawSnmpResponse('', 'Timeout', 1);
    });
    $this->app->instance(SnmpBackendInterface::class, $backend);

    $device = new Device(['hostname' => 'detect.example.com']);

    expect(app(ValidateDeviceAndCreate::class)->execute($device))->toBeTrue();
    expect(array_values(array_unique($tried)))->toBe(['wrong', 'correct']);
    $snmp = $device->pollingMethod(PollingMethodType::Snmp)?->fresh();
    expect($snmp)->not->toBeNull();
    expect($snmp->secret_id)->toBe($correct->id);
    expect((new SnmpPollingMethod)->version($snmp))->toBe('v2c');
    // a community is used as v2c
});

test('the first working default credential is preferred', function () {
    $v3 = Secret::factory()->create(['secret_type' => SecretType::SnmpV3, 'data' => ['authlevel' => 'authPriv', 'authname' => 'preferred', 'authpass' => 'authpass', 'cryptopass' => 'cryptopass']]);
    $v2c = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'public']]);
    LibrenmsConfig::set('snmp.default_credentials', [$v3->id, $v2c->id]);
    mockFpingUp();
    mockSnmpSysName('both-versions');

    // the device answers v3 and v2c
    $device = new Device(['hostname' => 'both-versions.example.com']);

    expect(app(ValidateDeviceAndCreate::class)->execute($device))->toBeTrue();
    $snmp = $device->pollingMethod(PollingMethodType::Snmp)?->fresh();
    expect($snmp)->not->toBeNull();
    expect($snmp->secret_id)->toBe($v3->id);
    expect((new SnmpPollingMethod)->version($snmp))->toBe('v3');
});

test('forced snmp without credentials uses first default credential', function () {
    $first = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity]);
    $second = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity]);
    LibrenmsConfig::set('snmp.default_credentials', [$second->id, $first->id]);

    $device = new Device(['hostname' => 'forced.example.com']);
    $pollingMethods = app(BuildDefaultPollingMethods::class)->execute($device);

    expect(app(ValidateDeviceAndCreate::class)->execute($device, $pollingMethods, force: true))->toBeTrue();
    expect($device->pollingMethod(PollingMethodType::Snmp)?->secret_id)->toBe($second->id);
});

test('methods are validated before they are saved', function () {
    $ipmi = Secret::factory()->create(['secret_type' => SecretType::Ipmi]);
    $snmp = new DevicePollingMethod(['method_type' => PollingMethodType::Snmp]);
    $snmp->setRelation('secret', $ipmi);

    $device = new Device(['hostname' => 'wrong-secret.example.com']);

    try {
        app(ValidateDeviceAndCreate::class)->execute($device, collect([$snmp]), force: true);
        $this->fail('An IPMI secret was saved for SNMP');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('secret_id');
    }
    expect($device->exists)->toBeFalse();
});

test('default methods follow icmp check', function () {
    $device = new Device(['hostname' => 'defaults.example.com']);

    $types = app(BuildDefaultPollingMethods::class)->execute($device)->pluck('method_type')->all();
    expect($types)->toBe([PollingMethodType::Icmp, PollingMethodType::Snmp]);

    LibrenmsConfig::set('icmp_check', false);
    $types = app(BuildDefaultPollingMethods::class)->execute($device)->pluck('method_type')->all();
    expect($types)->toBe([PollingMethodType::Snmp]);
});

test('forced snmp without any credentials is rejected', function () {
    LibrenmsConfig::set('snmp.default_credentials', []);

    $device = new Device(['hostname' => 'no-credentials.example.com']);
    $pollingMethods = app(BuildDefaultPollingMethods::class)->execute($device);

    $this->expectException(MissingSecretException::class);
    app(ValidateDeviceAndCreate::class)->execute($device, $pollingMethods, force: true);
});

test('unchecked methods are not discovered', function () {
    $default = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity]);
    LibrenmsConfig::set('snmp.default_credentials', [$default->id]);

    // an SNMP check of this host would fail and throw
    $device = new Device(['hostname' => 'unchecked.invalid']);
    $pollingMethods = app(BuildDefaultPollingMethods::class)->execute($device, ['methods' => [
        'icmp' => ['active' => true, 'enabled' => false],
        'snmp' => ['active' => true],
    ]]);

    expect(app(ValidateDeviceAndCreate::class)->execute($device, $pollingMethods, uncheckedMethods: [PollingMethodType::Snmp]))->toBeTrue();
    $snmp = $device->pollingMethod(PollingMethodType::Snmp);
    expect($snmp?->last_check_successful)->toBeNull();
    expect($snmp?->secret_id)->toBe($default->id);
});

test('ping only device is not checked for duplicate sys name', function () {
    LibrenmsConfig::set('allow_duplicate_sysName', false);
    Device::factory()->create(['hostname' => '10.0.0.1', 'sysName' => 'router1']);
    mockFpingUp();

    $device = new Device(['hostname' => 'router1']);
    $pollingMethods = app(BuildDefaultPollingMethods::class)->execute($device, ['methods' => ['icmp' => ['active' => true]]]);

    expect(app(ValidateDeviceAndCreate::class)->execute($device, $pollingMethods))->toBeTrue();
    expect($device->fresh()->sysName)->toBe('router1');
    // defaults to the hostname
});

test('given sys name is checked for duplicates', function () {
    LibrenmsConfig::set('allow_duplicate_sysName', false);
    Device::factory()->create(['hostname' => '10.0.0.1', 'sysName' => 'router1']);
    mockFpingUp();

    $device = new Device(['hostname' => 'ping-device', 'sysName' => 'router1']);
    $pollingMethods = app(BuildDefaultPollingMethods::class)->execute($device, ['methods' => ['icmp' => ['active' => true]]]);

    $this->expectException(HostSysnameExistsException::class);
    app(ValidateDeviceAndCreate::class)->execute($device, $pollingMethods);
});

test('empty snmp sys name is not checked for duplicates', function () {
    LibrenmsConfig::set('allow_duplicate_sysName', false);
    Device::factory()->create(['hostname' => '10.0.0.1', 'sysName' => 'router1']);
    mockFpingUp();
    mockSnmpSysName('');

    $device = new Device(['hostname' => 'router1']);
    $pollingMethods = app(BuildDefaultPollingMethods::class)->execute($device, ['methods' => [
        'snmp' => ['active' => true, 'secret_mode' => 'new', 'secret_type' => 'snmp-community', 'secret_data' => ['community' => 'public']],
    ]]);

    expect(app(ValidateDeviceAndCreate::class)->execute($device, $pollingMethods))->toBeTrue();
    expect($device->fresh()->sysName)->toBe('router1');
    // defaults to the hostname
});

test('duplicate snmp sys name is rejected', function () {
    LibrenmsConfig::set('allow_duplicate_sysName', false);
    Device::factory()->create(['hostname' => '10.0.0.1', 'sysName' => 'router1']);
    mockFpingUp();
    mockSnmpSysName('router1');

    $device = new Device(['hostname' => 'router1.example.com']);
    $pollingMethods = app(BuildDefaultPollingMethods::class)->execute($device, ['methods' => [
        'snmp' => ['active' => true, 'secret_mode' => 'new', 'secret_type' => 'snmp-community', 'secret_data' => ['community' => 'public']],
    ]]);

    $this->expectException(HostSysnameExistsException::class);
    app(ValidateDeviceAndCreate::class)->execute($device, $pollingMethods);
});

function mockFpingUp(): void
{
    $fping = Mockery::mock(Fping::class);
    $fping->shouldReceive('ping')->andReturn(FpingResponse::artificialUp());
    $this->app->instance(Fping::class, $fping);
}

/**
 * The device answers SNMP with this sysName.
 */
function mockSnmpSysName(string $sysName): void
{
    $backend = Mockery::mock(SnmpBackendInterface::class);
    $backend->shouldReceive('get')->andReturnUsing(fn ($target, $oids) => in_array('SNMPv2-MIB::sysName.0', $oids)
        ? new RawSnmpResponse("SNMPv2-MIB::sysName.0 = $sysName", '', 0)
        : new RawSnmpResponse('SNMPv2-MIB::sysObjectID.0 = .1.3.6.1.4.1.8072.3.2.10', '', 0));
    $backend->shouldIgnoreMissing(new RawSnmpResponse('', '', 0));
    $this->app->instance(SnmpBackendInterface::class, $backend);
}

/**
 * @return array<string, array{array<string, array<string, mixed>>, ?string, string}>
 */
dataset('osProvider', function () {
    return [
        'icmp only' => [['icmp' => ['active' => true]], null, 'ping'],
        'icmp only with os' => [['icmp' => ['active' => true]], 'linux', 'linux'],
        'snmp disabled' => [['icmp' => ['active' => true], 'snmp' => ['active' => true, 'enabled' => false]], null, 'ping'],
        'snmp' => [['icmp' => ['active' => true], 'snmp' => ['active' => true]], null, 'generic'],
    ];
});