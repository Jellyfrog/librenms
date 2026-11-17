<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Actions\Device\BuildPollingMethodCandidates;
use App\Actions\Device\DiscoverPollingMethod;
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\DevicePollingMethod;
use App\Models\Secret;
use LibreNMS\Data\Source\Snmp\RawSnmpResponse;
use LibreNMS\Data\Source\Snmp\SnmpBackendInterface;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Enum\SecretType;
use LibreNMS\Polling\Method\Config\SnmpConfig;
use PHPUnit\Framework\Attributes\DataProvider;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('anew device tries acommunity as v1', function (SecretType $secretType, array $settings, bool $exists, array $expected) {
    $probed = [];
    fakeSnmp($probed, fn (SnmpConfig $config): bool => $config->version === 'v1');

    $device = new Device(['hostname' => '192.0.2.1']);
    $device->exists = $exists;
    $deviceMethod = new DevicePollingMethod(['method_type' => PollingMethodType::Snmp, 'settings' => $settings]);
    $deviceMethod->setRelation('secret', new Secret([
        'description' => 'given',
        'secret_type' => $secretType,
        'data' => $secretType === SecretType::SnmpV3 ? ['authlevel' => 'noAuthNoPriv', 'authname' => 'user'] : ['community' => 'public'],
    ]));

    $result = app(DiscoverPollingMethod::class)->execute($device, $deviceMethod);

    expect(array_column($probed, 0))->toBe($expected);
    expect($result->isSuccess())->toBe(end($expected) === 'v1');

    // a v1 reply is saved as the version, otherwise the settings are unchanged
    expect($deviceMethod->settings)->toBe($result->isSuccess() ? [...$settings, 'version' => 'v1'] : $settings);
})->with('givenSecretProvider');

/**
 * @return array<string, array{SecretType, array<string, mixed>, bool, string[]}>
 */
dataset('givenSecretProvider', function () {
    return [
        'new device, community' => [SecretType::SnmpCommunity, ['port' => 1161], false, ['v2c', 'v1']],
        'new device, community, given version' => [SecretType::SnmpCommunity, ['version' => 'v2c'], false, ['v2c']],
        'new device, v3' => [SecretType::SnmpV3, [], false, ['v3']],
        'existing device keeps its version' => [SecretType::SnmpCommunity, [], true, ['v2c']],
    ];
});

test('default credentials try communities as v1 last', function () {
    $public = Secret::create(['description' => 'public', 'secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'public']]);
    $v3 = Secret::create(['description' => 'v3', 'secret_type' => SecretType::SnmpV3, 'data' => ['authlevel' => 'noAuthNoPriv', 'authname' => 'user']]);
    $private = Secret::create(['description' => 'private', 'secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'private']]);
    LibrenmsConfig::set('snmp.default_credentials', [$public->id, $v3->id, $private->id]);

    $probed = [];
    fakeSnmp($probed, fn (SnmpConfig $config): bool => $config->version === 'v1' && $config->community === 'private');

    $deviceMethod = new DevicePollingMethod(['method_type' => PollingMethodType::Snmp]);
    $result = app(DiscoverPollingMethod::class)->execute(new Device(['hostname' => '192.0.2.1']), $deviceMethod);

    expect($result->isSuccess())->toBeTrue();
    expect($probed)->toBe([['v2c', 'public'], ['v3', null], ['v2c', 'private'], ['v1', 'public'], ['v1', 'private']]);
    expect($deviceMethod->secret_id)->toBe($private->id);
    expect($deviceMethod->secret?->id)->toBe($private->id);
    expect($deviceMethod->settings)->toBe(['version' => 'v1']);
});

test('failure lists every candidate', function () {
    $public = Secret::create(['description' => 'Default public', 'secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'public']]);
    LibrenmsConfig::set('snmp.default_credentials', [$public->id]);
    $probed = [];
    fakeSnmp($probed, fn (): bool => false);

    $deviceMethod = new DevicePollingMethod(['method_type' => PollingMethodType::Snmp]);
    $result = app(DiscoverPollingMethod::class)->execute(new Device(['hostname' => '192.0.2.1']), $deviceMethod);

    expect($result->isSuccess())->toBeFalse();
    expect($result->reasons())->toBe([
        'SNMP v2c: No reply using credential "Default public"',
        'SNMP v1: No reply using credential "Default public"',
    ]);
    expect($deviceMethod->secret_id)->toBeNull('nothing worked, the method is unchanged');
    expect($deviceMethod->settings)->toBeNull();
});

test('other methods are tried as they are', function () {
    $deviceMethod = new DevicePollingMethod(['method_type' => PollingMethodType::Ipmi, 'settings' => ['port' => 623]]);

    $candidates = app(BuildPollingMethodCandidates::class)->execute(new Device, $deviceMethod);

    expect($candidates)->toHaveCount(1);
    $this->assertNotSame($deviceMethod, $candidates[0], 'a copy, the method is not changed');
    expect($candidates[0]->settings)->toBe(['port' => 623]);
});

/**
 * @param  list<array{string, ?string}>  $probed  filled with the version and community of each probe
 * @param  callable(SnmpConfig): bool  $replies
 */
function fakeSnmp(array &$probed, callable $replies): void
{
    $backend = Mockery::mock(SnmpBackendInterface::class);
    $backend->shouldReceive('get')->andReturnUsing(function ($target, $oids, SnmpConfig $config) use (&$probed, $replies) {
        $probed[] = [$config->version, $config->community];

        return $replies($config)
            ? new RawSnmpResponse('SNMPv2-MIB::sysObjectID.0 = .1.3.6.1.4.1.8072.3.2.10', '', 0)
            : new RawSnmpResponse('', 'Timeout: No Response from udp:192.0.2.1:161.', 1);
    });
    $this->app->instance(SnmpBackendInterface::class, $backend);
}