<?php

/**
 * SnmpTranslateTest.php
 *
 * -Description-
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
 * @link       https://www.librenms.org
 *
 * @copyright  2022 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use App\Models\Device;

uses(\LibreNMS\Tests\TestCase::class);

test('simple input', function () {
    $actual = \SnmpQuery::numeric()->translate('IF-MIB::ifTable');
    expect($actual)->toEqual('.1.3.6.1.2.1.2.2');

    $actual = \SnmpQuery::numeric()->mibs(['IF-MIB'], append: false)->translate('ifTable');
    expect($actual)->toEqual('.1.3.6.1.2.1.2.2');

    $actual = \SnmpQuery::numeric()->mibs(['ALL'], append: false)->translate('ifTable');
    expect($actual)->toEqual('.1.3.6.1.2.1.2.2');

    $actual = \SnmpQuery::translate('IF-MIB::ifTable');
    expect($actual)->toEqual('IF-MIB::ifTable');

    $actual = \SnmpQuery::numeric()->translate('.1.3.6.1.2.1.2.2');
    expect($actual)->toEqual('.1.3.6.1.2.1.2.2');

    $actual = \SnmpQuery::mibs(['IF-MIB'])->translate('.1.3.6.1.2.1.2.2');
    expect($actual)->toEqual('IF-MIB::ifTable');

    $actual = \SnmpQuery::numeric()->translate('1.3.6.1.2.1.2.2');
    expect($actual)->toEqual('.1.3.6.1.2.1.2.2');

    $actual = \SnmpQuery::mibs(['ALL'], append: false)->translate('.1.3.6.1.2.1.2.2');
    expect($actual)->toEqual('RFC1213-MIB::ifTable');

    $actual = \SnmpQuery::numeric()->mibs(['IP-MIB'])->translate('ifTable');
    expect($actual)->toEqual('.1.3.6.1.2.1.2.2');

    $actual = \SnmpQuery::mibs(['IP-MIB'])->translate('ifTable');
    expect($actual)->toEqual('IF-MIB::ifTable');

    // with index
    $actual = \SnmpQuery::numeric()->translate('IF-MIB::ifTable.0');
    expect($actual)->toEqual('.1.3.6.1.2.1.2.2.0');

    $actual = \SnmpQuery::numeric()->mibs(['IF-MIB'])->translate('ifTable.0');
    expect($actual)->toEqual('.1.3.6.1.2.1.2.2.0');

    $actual = \SnmpQuery::numeric()->mibs(['ALL'], append: false)->translate('ifTable.0');
    expect($actual)->toEqual('.1.3.6.1.2.1.2.2.0');

    $actual = \SnmpQuery::translate('IF-MIB::ifTable.0');
    expect($actual)->toEqual('IF-MIB::ifTable.0');

    $actual = \SnmpQuery::numeric()->translate('.1.3.6.1.2.1.2.2.0');
    expect($actual)->toEqual('.1.3.6.1.2.1.2.2.0');

    $actual = \SnmpQuery::mibs(['IF-MIB'])->translate('.1.3.6.1.2.1.2.2.0');
    expect($actual)->toEqual('IF-MIB::ifTable.0');

    $actual = \SnmpQuery::numeric()->translate('1.3.6.1.2.1.2.2.0');
    expect($actual)->toEqual('.1.3.6.1.2.1.2.2.0');

    $actual = \SnmpQuery::mibs(['ALL'], append: false)->translate('.1.3.6.1.2.1.2.2.0');
    expect($actual)->toEqual('RFC1213-MIB::ifTable.0');

    $actual = \SnmpQuery::mibs(['IP-MIB'])->translate('ifTable.0');
    expect($actual)->toEqual('IF-MIB::ifTable.0');

    $actual = \SnmpQuery::mibs(['SNMPv2-MIB'])->translate('iso.3.6.1.2.1.1.1.0');
    expect($actual)->toEqual('SNMPv2-MIB::sysDescr.0');

    $actual = \SnmpQuery::numeric()->mibs(['SNMPv2-MIB'])->translate('iso.3.6.1.2.1.1.1.0');
    expect($actual)->toEqual('.1.3.6.1.2.1.1.1.0');
});

test('failed input', function () {
    $actual = \SnmpQuery::translate('ifTable');
    expect($actual)->toEqual('IF-MIB::ifTable');

    $actual = \SnmpQuery::numeric()->mibs([], append: false)->translate('ifTable');
    expect($actual)->toEqual('');

    $actual = \SnmpQuery::mibs([], append: false)->translate('ifTable');
    expect($actual)->toEqual('');

    $actual = \SnmpQuery::numeric()->mibs(['ASDF-MIB', 'SNMPv2-MIB'], append: false)->translate('ifTable');
    expect($actual)->toEqual('');

    $actual = \SnmpQuery::mibs([], append: false)->translate('ifTable');
    expect($actual)->toEqual('');

    $actual = \SnmpQuery::numeric()->mibs([], append: false)->translate('ifTable');
    expect($actual)->toEqual('');

    $actual = \SnmpQuery::mibs([], append: false)->translate('ifTable');
    expect($actual)->toEqual('');
});

test('complex input', function () {
    $actual = \SnmpQuery::mibs(['RFC1213-MIB', 'IF-MIB'], append: false)->translate('.1.3.6.1.2.1.2.2');
    expect($actual)->toEqual('RFC1213-MIB::ifTable');

    $actual = \SnmpQuery::mibs(['IF-MIB', 'RFC1213-MIB'], append: false)->translate('.1.3.6.1.2.1.2.2');
    expect($actual)->toEqual('IF-MIB::ifTable');

    $actual = \SnmpQuery::translate('ifTable');
    expect($actual)->toEqual('IF-MIB::ifTable');

    $actual = \SnmpQuery::mibs(['RFC1213-MIB', 'IF-MIB'], append: false)->translate('ifTable');
    expect($actual)->toEqual('RFC1213-MIB::ifTable');

    $actual = \SnmpQuery::mibs(['IF-MIB', 'RFC1213-MIB'], append: false)->translate('ifTable');
    expect($actual)->toEqual('IF-MIB::ifTable');

    // partial numeric
    $device = Device::factory()->make(['os' => 'dlink']);
    $actual = \SnmpQuery::device($device)->numeric()->mibs(['EQUIPMENT-MIB', 'DLINKSW-ENTITY-EXT-MIB'], append: false)->translate('.1.3.6.1.4.1.171.14.5.1.4.1.4.1.dram');
    expect($actual)->toEqual('.1.3.6.1.4.1.171.14.5.1.4.1.4.1.1');

    $actual = \SnmpQuery::device($device)->numeric()->mibs(['EQUIPMENT-MIB', 'DLINKSW-ENTITY-EXT-MIB'], append: false)->translate('iso.3.6.1.4.1.171.14.5.1.4.1.4.1.dram');
    expect($actual)->toEqual('.1.3.6.1.4.1.171.14.5.1.4.1.4.1.1');
});
