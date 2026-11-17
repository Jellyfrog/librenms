<?php

/**
 * addhostCliTest.php
 *
 * Tests for lnms device:add cli tool
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link https://www.librenms.org
 *
 * @copyright  2020 Lars Elgtvedt Susaas
 * @author     Lars Elgtvedt Susaas
 */

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\Secret;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Enum\SecretType;
use PHPUnit\Framework\Attributes\TestDox;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('clisnmp v1', function () {
    $this->artisan('device:add', ['device spec' => $this->hostName, '--force' => true, '-c' => 'community', '--v1' => true])
        ->assertExitCode(0)
        ->execute();

    $device = Device::findByHostname($this->hostName);
    expect($device)->not->toBeNull();

    $snmpMethod = $device->pollingMethod(PollingMethodType::Snmp);
    expect($snmpMethod)->not->toBeNull();
    $secret = $snmpMethod->secret;
    expect($secret)->not->toBeNull();
    expect($secret->data)->toBe(['community' => 'community']);
    expect($snmpMethod->settings['version'])->toEqual('v1');

    $icmpMethod = $device->pollingMethod(PollingMethodType::Icmp);
    expect($icmpMethod)->not->toBeNull();
    expect($icmpMethod->enabled)->toBeTrue();
});

test('clisnmp v2', function () {
    $this->artisan('device:add', ['device spec' => $this->hostName, '--force' => true, '-c' => 'community', '--v2c' => true])
        ->assertExitCode(0)
        ->execute();

    $device = Device::findByHostname($this->hostName);
    expect($device)->not->toBeNull();

    $snmpMethod = $device->pollingMethod(PollingMethodType::Snmp);
    expect($snmpMethod)->not->toBeNull();
    $secret = $snmpMethod->secret;
    expect($secret)->not->toBeNull();
    expect($secret->data)->toBe(['community' => 'community']);
    expect($snmpMethod->settings['version'])->toBe('v2c');
    // a given version is not tried as v1
});

test('clisnmp v3 user and pw', function () {
    $this->artisan('device:add', ['device spec' => $this->hostName, '--force' => true, '-u' => 'SecName', '-A' => 'AuthPW', '-X' => 'PrivPW', '--v3' => true])
    ->assertExitCode(0)
    ->execute();

    $device = Device::findByHostname($this->hostName);
    expect($device)->not->toBeNull();

    $snmpMethod = $device->pollingMethod(PollingMethodType::Snmp);
    expect($snmpMethod)->not->toBeNull();
    $secret = $snmpMethod->secret;
    expect($secret)->not->toBeNull();
    expect($secret->secret_type)->toBe(SecretType::SnmpV3);
    expect($secret->data['authname'])->toEqual('SecName');
    expect($secret->data['authpass'])->toEqual('AuthPW');
    expect($secret->data['cryptopass'])->toEqual('PrivPW');
});

test('port association mode', function () {
    setDefaultCredentials('v1');

    $modes = ['ifIndex', 'ifName', 'ifDescr', 'ifAlias'];
    foreach ($modes as $mode) {
        $host = 'hostName' . $mode;
        $this->artisan('device:add', ['device spec' => $host, '--force' => true, '-p' => $mode, '--v1' => true])
            ->assertExitCode(0)
            ->execute();

        $device = Device::findByHostname($host);
        expect($device)->not->toBeNull();
        $snmpMethod = $device->pollingMethod(PollingMethodType::Snmp);
        expect($snmpMethod)->not->toBeNull();

        // explicitly set values are stored, even when they match the default
        expect($snmpMethod->settings['port_association_mode'] ?? null)->toEqual($mode, 'Wrong port association mode ' . $mode);
    }
});

test('snmp transport', function () {
    setDefaultCredentials('v1');

    $modes = ['udp', 'udp6', 'tcp', 'tcp6'];
    foreach ($modes as $mode) {
        $host = 'hostName' . $mode;
        $this->artisan('device:add', ['device spec' => $host, '--force' => true, '-t' => $mode, '--v1' => true])
            ->assertExitCode(0)
            ->execute();

        $device = Device::findByHostname($host);
        $snmpMethod = $device->pollingMethod(PollingMethodType::Snmp);

        expect($snmpMethod->settings['transport'])->toEqual($mode, 'Wrong snmp transport (udp/tcp) ipv4/ipv6');
    }

    // not given, so left to the default
    $this->artisan('device:add', ['device spec' => 'hostNameDefault', '--force' => true, '--v1' => true])
        ->assertExitCode(0)
        ->execute();
    $snmpMethod = Device::findByHostname('hostNameDefault')->pollingMethod(PollingMethodType::Snmp);
    $this->assertArrayNotHasKey('transport', $snmpMethod->settings ?? []);
});

test('snmp v3 auth protocol', function () {
    $modes = \LibreNMS\SNMPCapabilities::supportedAuthAlgorithms();
    foreach ($modes as $mode) {
        $host = 'hostName' . $mode;
        $this->artisan('device:add', ['device spec' => $host, '--force' => true, '-u' => 'SecName', '-a' => $mode, '--v3' => true])
            ->assertExitCode(0)
            ->execute();

        $device = Device::findByHostname($host);
        $snmpMethod = $device->pollingMethod(PollingMethodType::Snmp);
        expect($snmpMethod->secret->data['authalgo'])->toEqual(strtoupper((string) $mode), 'Wrong snmp v3 password algorithm');
    }
});

test('snmp v3 privacy protocol', function () {
    $modes = \LibreNMS\SNMPCapabilities::supportedCryptoAlgorithms();
    foreach ($modes as $mode) {
        $host = 'hostName' . $mode;
        $this->artisan('device:add', ['device spec' => $host, '--force' => true, '-u' => 'SecName', '-x' => $mode, '--v3' => true])
            ->assertExitCode(0)
            ->execute();

        $device = Device::findByHostname($host);
        $snmpMethod = $device->pollingMethod(PollingMethodType::Snmp);
        expect($snmpMethod->secret->data['cryptoalgo'])->toEqual(strtoupper((string) $mode), 'Wrong snmp v3 crypt algorithm');
    }
});

test('cliping', function () {
    $this->artisan('device:add', ['device spec' => $this->hostName, '--force' => true, '-P' => true, '-o' => 'nameOfOS', '-w' => 'hardware', '-s' => 'System', '--v1' => true])
        ->assertExitCode(0)
        ->execute();

    $device = Device::findByHostname($this->hostName);
    expect($device)->not->toBeNull();

    expect($device->hardware)->toEqual('hardware', 'Wrong hardware name');
    expect($device->os)->toEqual('nameOfOS', 'Wrong os name');
    expect($device->sysName)->toEqual('system', 'Wrong system name');

    expect($device->pollingMethod(PollingMethodType::Snmp))->toBeNull();
    expect($device->pollingMethod(PollingMethodType::Icmp))->not->toBeNull();
});

test('existing device', function () {
    // without credentials, a forced add uses the default credentials
    $secret = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'public']]);
    LibrenmsConfig::set('snmp.default_credentials', [$secret->id]);

    $this->artisan('device:add', ['device spec' => 'existing', '--force' => true])
        ->assertExitCode(0)
        ->execute();
    $this->artisan('device:add', ['device spec' => 'existing'])
        ->assertExitCode(3)
        ->execute();
});

test('explicit v3 credentials matching the defaults are kept', function () {
    setDefaultCredentials('v3');

    $this->artisan('device:add', ['device spec' => $this->hostName, '--force' => true, '--v3' => true, '-u' => 'root'])
        ->assertExitCode(0)
        ->execute();

    $snmpMethod = Device::findByHostname($this->hostName)->pollingMethod(PollingMethodType::Snmp);
    $data = $snmpMethod->secret->data;
    expect($snmpMethod->secret->secret_type)->toBe(SecretType::SnmpV3);
    expect($data['authname'])->toBe('root');
    expect($data['authlevel'])->toBe('noAuthNoPriv');
});

test('v3 credentials without version', function () {
    $this->artisan('device:add', ['device spec' => $this->hostName, '--force' => true, '-u' => 'SecName', '-A' => 'AuthPW', '-a' => 'SHA'])
        ->assertExitCode(0)
        ->execute();

    $snmpMethod = Device::findByHostname($this->hostName)->pollingMethod(PollingMethodType::Snmp);
    $data = $snmpMethod->secret->data;
    expect($snmpMethod->secret->secret_type)->toBe(SecretType::SnmpV3);
    expect($data['authname'])->toBe('SecName');
    expect($data['authlevel'])->toBe('authNoPriv');
    expect($data['authalgo'])->toBe('SHA');
    expect($data['cryptoalgo'])->toBe('AES');
    // default
});

test('version only uses default credentials for that version', function () {
    $secrets = setDefaultCredentials('v3', 'v2c');

    $this->artisan('device:add', ['device spec' => $this->hostName, '--force' => true, '--v1' => true])
        ->assertExitCode(0)
        ->execute();

    // a community is a v1 credential too
    $snmpMethod = Device::findByHostname($this->hostName)->pollingMethod(PollingMethodType::Snmp);
    expect($snmpMethod->secret_id)->toBe($secrets['v2c']->id);
    expect($snmpMethod->settings['version'])->toBe('v1');
});

test('version only without default credentials', function () {
    setDefaultCredentials('v2c');

    $this->artisan('device:add', ['device spec' => $this->hostName, '--force' => true, '--v3' => true])
        ->expectsOutput(trans('exceptions.missing_secret', ['method' => PollingMethodType::Snmp->label()]))
        ->assertExitCode(1)
        ->execute();

    expect(Device::findByHostname($this->hostName))->toBeNull();
});

/**
 * Replace the default credentials with one for each SNMP version, in order. v1 and v2c are both communities.
 *
 * @return array<string, Secret>
 */
function setDefaultCredentials(string ...$versions): array
{
    $secrets = [];
    foreach ($versions as $version) {
        $secrets[$version] = $version === 'v3'
            ? Secret::factory()->create(['secret_type' => SecretType::SnmpV3, 'data' => ['authlevel' => 'noAuthNoPriv', 'authname' => 'public-v3']])
            : Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => "public-$version"]]);
    }
    LibrenmsConfig::set('snmp.default_credentials', array_values(array_map(fn (Secret $secret) => $secret->id, $secrets)));

    return $secrets;
}