<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\Secret;
use LibreNMS\Enum\SecretType;
use LibreNMS\Scan\ScanOptions;
use PHPUnit\Framework\Attributes\TestDox;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

uses(\LibreNMS\Tests\Traits\FakesNetworkTools::class);

beforeEach(function () {
    $this->fakeFping([]);
    LibrenmsConfig::set('snmp.default_credentials', []);
});

test('no network', function () {
    LibrenmsConfig::set('nets', []);

    $this->artisan('scan:network')
        ->expectsOutputToContain(__('commands.scan:network.errors.no_network'))
        ->assertExitCode(1);
});

test('invalid network', function () {
    $this->artisan('scan:network', ['network' => ['not-an-ip']])
        ->expectsOutputToContain(__('commands.scan:network.errors.invalid_network', ['network' => 'not-an-ip']))
        ->assertExitCode(2);
});

test('missing fping', function () {
    $this->setBinary('fping', '/nonexistent/fping');

    $this->artisan('scan:network', ['network' => ['192.0.2.1/32']])
        ->expectsOutputToContain(__('commands.scan:network.errors.missing_binary', ['binary' => 'fping']))
        ->assertExitCode(3);
});

test('nets setting', function () {
    LibrenmsConfig::set('nets', ['192.0.2.0/30']);

    $this->artisan('scan')
        ->expectsOutputToContain('Scanned 2 addresses in')
        ->assertExitCode(0);
});

test('several networks', function () {
    $this->artisan('scan:network', ['network' => ['192.0.2.0/30', '192.0.2.4/31']])
        ->expectsOutput('.')
        ->expectsOutputToContain('Scanned 4 addresses in')
        ->assertExitCode(0);
});

test('legend', function () {
    $this->artisan('scan:network', ['network' => ['192.0.2.1/32'], '--legend' => true])
        ->expectsOutputToContain(__('commands.scan:network.legend'))
        ->expectsOutputToContain(__('commands.scan:network.outcomes.Excluded'))
        ->assertExitCode(0);
});

test('excluded', function () {
    LibrenmsConfig::set('autodiscovery.nets-exclude', ['192.0.2.1/32', 'not-a-network']);

    $this->artisan('scan:network', ['network' => ['192.0.2.0/30']])
        ->expectsOutputToContain(__('commands.scan:network.errors.invalid_excluded_network', ['network' => 'not-a-network']))
        ->expectsOutputToContain(__('commands.scan:network.outcomes.Excluded'))
        ->expectsOutputToContain('Scanned 2 addresses in')
        ->assertExitCode(0);
});

test('verbose', function () {
    Device::factory()->create(['hostname' => 'known.example.com', 'ip' => '192.0.2.1']);

    $this->artisan('scan:network', ['network' => ['192.0.2.1/32'], '-v' => true])
        ->expectsOutputToContain('192.0.2.1')
        ->expectsOutputToContain(__('exceptions.host_exists.hostname_exists', ['hostname' => 'known.example.com']))
        ->expectsOutputToContain(__('commands.scan:network.outcomes.Known'))
        ->expectsOutputToContain('Scanned 1 address in')
        ->assertExitCode(0);
});

test('no snmp credentials', function () {
    $this->artisan('scan:network', ['network' => ['192.0.2.1/32']])
        ->expectsOutputToContain(__('commands.scan:network.errors.no_snmp_credentials'))
        ->assertExitCode(0);
});

test('default snmp configs', function () {
    $v3 = Secret::factory()->create(['secret_type' => SecretType::SnmpV3, 'data' => ['authlevel' => 'noAuthNoPriv', 'authname' => 'scanner']]);
    $v2c = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'scan-community']]);
    Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'not-a-default']]);
    LibrenmsConfig::set('snmp.default_credentials', [$v2c->id, $v3->id]);

    $configs = ScanOptions::defaultSnmpConfigs();

    // a community is tried as v1 after the other credentials, like a new device is added
    expect(array_map(fn (array $c) => [$c[0], $c[1]->version], $configs))->toBe([[$v2c->id, 'v2c'], [$v3->id, 'v3'], [$v2c->id, 'v1']]);
    expect($configs[0][1]->community)->toBe('scan-community');
    expect($configs[2][1]->community)->toBe('scan-community');
    expect($configs[1][1]->authname)->toBe('scanner');
    expect($configs[0][1]->timeout)->toBe(1);
    expect($configs[0][1]->retries)->toBe(0);
    expect(ScanOptions::fromConfig(pingOnly: true)->snmpConfigs)->toBe([], 'ping only does not probe SNMP');
});