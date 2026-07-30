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

use App\Models\Device;

uses(LibreNMS\Tests\DBTestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function (): void {
    $this->hostName = 'testHost';
});

test('CLI SNMP v1', function (): void {
    $this->artisan('device:add', ['device spec' => $this->hostName, '--force' => true, '-c' => 'community', '--v1' => true])
        ->assertExitCode(0)
        ->execute();

    $device = Device::findByHostname($this->hostName);
    expect($device)->not->toBeNull();

    expect($device->snmp_disable)->toEqual(0, 'snmp is disabled');
    expect($device->community)->toEqual('community', 'Wrong snmp community');
    expect($device->snmpver)->toEqual('v1', 'Wrong snmp version');
});

test('CLI SNMP v2', function (): void {
    $this->artisan('device:add', ['device spec' => $this->hostName, '--force' => true, '-c' => 'community', '--v2c' => true])
        ->assertExitCode(0)
        ->execute();

    $device = Device::findByHostname($this->hostName);
    expect($device)->not->toBeNull();

    expect($device->snmp_disable)->toEqual(0, 'snmp is disabled');
    expect($device->community)->toEqual('community', 'Wrong snmp community');
    expect($device->snmpver)->toEqual('v2c', 'Wrong snmp version');
});

test('CLI SNMP v3 user and password', function (): void {
    $this->artisan('device:add', ['device spec' => $this->hostName, '--force' => true, '-u' => 'SecName', '-A' => 'AuthPW', '-X' => 'PrivPW', '--v3' => true])
    ->assertExitCode(0)
    ->execute();

    $device = Device::findByHostname($this->hostName);
    expect($device)->not->toBeNull();

    expect($device->snmp_disable)->toEqual(0, 'snmp is disabled');
    expect($device->authlevel)->toEqual('authPriv', 'Wrong snmp v3 authlevel');
    expect($device->authname)->toEqual('SecName', 'Wrong snmp v3 security username');
    expect($device->authpass)->toEqual('AuthPW', 'Wrong snmp v3 authentication password');
    expect($device->cryptopass)->toEqual('PrivPW', 'Wrong snmp v3 crypto password');
    expect($device->snmpver)->toEqual('v3', 'Wrong snmp version');
});

test('port association mode', function (): void {
    $modes = ['ifIndex', 'ifName', 'ifDescr', 'ifAlias'];
    foreach ($modes as $index => $mode) {
        $host = 'hostName' . $mode;
        $this->artisan('device:add', ['device spec' => $host, '--force' => true, '-p' => $mode, '--v1' => true])
            ->assertExitCode(0)
            ->execute();

        $device = Device::findByHostname($host);
        expect($device)->not->toBeNull();
        expect($device->port_association_mode)->toEqual($index + 1, 'Wrong port association mode ' . $mode);
    }
});

test('SNMP transport', function (): void {
    $modes = ['udp', 'udp6', 'tcp', 'tcp6'];
    foreach ($modes as $mode) {
        $host = 'hostName' . $mode;
        $this->artisan('device:add', ['device spec' => $host, '--force' => true, '-t' => $mode, '--v1' => true])
            ->assertExitCode(0)
            ->execute();

        $device = Device::findByHostname($host);
        expect($device)->not->toBeNull();

        expect($device->transport)->toEqual($mode, 'Wrong snmp transport (udp/tcp) ipv4/ipv6');
    }
});

test('SNMP v3 auth protocol', function (): void {
    $modes = LibreNMS\SNMPCapabilities::supportedAuthAlgorithms();
    foreach ($modes as $mode) {
        $host = 'hostName' . $mode;
        $this->artisan('device:add', ['device spec' => $host, '--force' => true, '-a' => $mode, '--v3' => true])
            ->assertExitCode(0)
            ->execute();

        $device = Device::findByHostname($host);
        expect($device)->not->toBeNull();

        expect($device->authalgo)->toEqual(strtoupper((string) $mode), 'Wrong snmp v3 password algorithm');
    }
});

test('SNMP v3 privacy protocol', function (): void {
    $modes = LibreNMS\SNMPCapabilities::supportedCryptoAlgorithms();
    foreach ($modes as $mode) {
        $host = 'hostName' . $mode;
        $this->artisan('device:add', ['device spec' => $host, '--force' => true, '-x' => $mode, '--v3' => true])
            ->assertExitCode(0)
            ->execute();

        $device = Device::findByHostname($host);
        expect($device)->not->toBeNull();

        expect($device->cryptoalgo)->toEqual(strtoupper((string) $mode), 'Wrong snmp v3 crypt algorithm');
    }
});

test('CLI ping', function (): void {
    $this->artisan('device:add', ['device spec' => $this->hostName, '--force' => true, '-P' => true, '-o' => 'nameOfOS', '-w' => 'hardware', '-s' => 'System', '--v1' => true])
        ->assertExitCode(0)
        ->execute();

    $device = Device::findByHostname($this->hostName);
    expect($device)->not->toBeNull();

    expect($device->snmp_disable)->toEqual(1, 'snmp is not disabled');
    expect($device->hardware)->toEqual('hardware', 'Wrong hardware name');
    expect($device->os)->toEqual('nameOfOS', 'Wrong os name');
    expect($device->sysName)->toEqual('system', 'Wrong system name');
});

test('existing device', function (): void {
    $this->artisan('device:add', ['device spec' => 'existing', '--force' => true])
        ->assertExitCode(0)
        ->execute();
    $this->artisan('device:add', ['device spec' => 'existing'])
        ->assertExitCode(3)
        ->execute();
});
