<?php

/**
 * SnmpResponseTest.php
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
 * @copyright  2021 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use App\Facades\LibrenmsConfig;
use LibreNMS\Data\Source\SnmpResponse;

test('simple', function () {
    $response = new SnmpResponse("IF-MIB::ifDescr[1] = lo\nIF-MIB::ifDescr[2] = enp4s0\n");

    expect($response->isValid())->toBeTrue();
    expect($response->values())->toBe(['IF-MIB::ifDescr[1]' => 'lo', 'IF-MIB::ifDescr[2]' => 'enp4s0']);
    expect($response->value())->toBe('lo');
    expect($response->table())->toBe(['IF-MIB::ifDescr' => [1 => 'lo', 2 => 'enp4s0']]);
    expect($response->table(1))->toBe([1 => ['IF-MIB::ifDescr' => 'lo'], 2 => ['IF-MIB::ifDescr' => 'enp4s0']]);

    // snmptranslate type response
    $response = new SnmpResponse("IF-MIB::ifDescr\n");

    expect($response->isValid())->toBeTrue();
    expect($response->values())->toBe(['' => 'IF-MIB::ifDescr']);
    expect($response->value())->toBe('IF-MIB::ifDescr');
    expect($response->table())->toBe(['' => 'IF-MIB::ifDescr']);

    // unescaped strings
    $response = new SnmpResponse("Q-BRIDGE-MIB::dot1qVlanStaticName[1] = \"\\default\\\"\nQ-BRIDGE-MIB::dot1qVlanStaticName[6] = \\single\\\nQ-BRIDGE-MIB::dot1qVlanStaticName[9] = \\\\double\\\\\n");
    expect($response->isValid())->toBeTrue();
    expect($response->value())->toBe('default');
    LibrenmsConfig::set('snmp.unescape', false);
    expect($response->values())->toBe([
        'Q-BRIDGE-MIB::dot1qVlanStaticName[1]' => 'default',
        'Q-BRIDGE-MIB::dot1qVlanStaticName[6]' => '\\single\\',
        'Q-BRIDGE-MIB::dot1qVlanStaticName[9]' => '\\\\double\\\\',
    ]);
    expect($response->table())->toBe(['Q-BRIDGE-MIB::dot1qVlanStaticName' => [
        1 => 'default',
        6 => '\\single\\',
        9 => '\\\\double\\\\',
    ]]);

    LibrenmsConfig::set('snmp.unescape', true); // for buggy versions of net-snmp
    $response = new SnmpResponse("Q-BRIDGE-MIB::dot1qVlanStaticName[1] = \"\\default\\\"\nQ-BRIDGE-MIB::dot1qVlanStaticName[6] = \\single\\\nQ-BRIDGE-MIB::dot1qVlanStaticName[9] = \\\\double\\\\\n");
    expect($response->values())->toBe([
        'Q-BRIDGE-MIB::dot1qVlanStaticName[1]' => 'default',
        'Q-BRIDGE-MIB::dot1qVlanStaticName[6]' => 'single',
        'Q-BRIDGE-MIB::dot1qVlanStaticName[9]' => '\\double\\',
    ]);
    expect($response->table())->toBe(['Q-BRIDGE-MIB::dot1qVlanStaticName' => [
        1 => 'default',
        6 => 'single',
        9 => '\\double\\',
    ]]);
});

test('value fetching', function () {
    $response = new SnmpResponse("IF-MIB::ifDescr[1] = lo\nIF-MIB::ifDescr[2] = enp4s0\nIF-MIB::ifAlias[1] = alias one\nIF-MIB::ifAlias[2] = alias two\n\n");

    expect($response->value())->toBe('lo');
    expect($response->value('IF-MIB::ifDescr[1]'))->toBe('lo');
    expect($response->value('IF-MIB::ifDescr[2]'))->toBe('enp4s0');
    expect($response->value('IF-MIB::ifDescr.2'))->toBe('enp4s0');
    expect($response->value(['IF-MIB::ifDescr[1]', 'IF-MIB::ifDescr[2]']))->toBe('lo');
    expect($response->value(['IF-MIB::ifName[2]', 'IF-MIB::ifDescr[2]', 'IF-MIB::ifDescr[1]']))->toBe('enp4s0');
    expect($response->value(['IF-MIB::ifDescr.1', 'IF-MIB::ifDescr.2']))->toBe('lo');

    expect($response->value('IF-MIB::ifDescr'))->toBe('lo');
    expect($response->value('IF-MIB::ifAlias'))->toBe('alias one');
    expect($response->value('ifAlias'))->toBe('');
    expect($response->value('IF-MIB:'))->toBe('');
    expect($response->value('IF-MIB::ifA'))->toBe('');

    $response = new SnmpResponse("ifName.1 = lo\nifAlias.3 = cust42\nifAlias.4 = cust51\n\n");
    expect($response->value('ifName'))->toBe('lo');
    expect($response->value('ifName'))->toBe('lo');
    expect($response->value('ifAlias'))->toBe('cust42');
    expect($response->value('ifAlias.4'))->toBe('cust51');
    expect($response->value('ifAlias[4]'))->toBeNull();
});

test('empty values', function () {
    // empty values
    $response = new SnmpResponse("IF-MIB::ifAlias[1] = \nIF-MIB::ifAlias[2] = 0\nIF-MIB::ifAlias[3] = \"\"\n\n");
    expect($response->isValid())->toBeTrue();
    expect($response->value())->toBe('');
    expect($response->value('IF-MIB::ifAlias[1]'))->toBe('');
    expect($response->value('IF-MIB::ifAlias[2]'))->toBe('0');
    expect($response->value('IF-MIB::ifAlias[3]'))->toBe('');
    expect($response->value(['IF-MIB::ifAlias[1]', 'IF-MIB::ifAlias[2]', 'IF-MIB::ifAlias[3]']))->toBe('0');
});

test('valuesByIndex', function () {
    $response = new SnmpResponse("IF-MIB::ifIndex[1] = 1\nIF-MIB::ifIndex[2] = 2\nIF-MIB::ifDescr[1] = lo\nIF-MIB::ifDescr[2] = enp4s0\n\n");

    expect($response->isValid())->toBeTrue();
    expect($response->valuesByIndex())->toBe([
        1 => [
            'IF-MIB::ifIndex' => '1',
            'IF-MIB::ifDescr' => 'lo',
        ],
        2 => [
            'IF-MIB::ifIndex' => '2',
            'IF-MIB::ifDescr' => 'enp4s0',
        ],
    ]);

    $existing = [0 => 'first', 1 => ['IF-MIB::ifDescr' => 'previous', 'IF-MIB::ifName' => 'lo']];
    expect($response->valuesByIndex($existing))->toBe([
        0 => 'first',
        1 => [
            'IF-MIB::ifIndex' => '1',
            'IF-MIB::ifDescr' => 'lo',
            'IF-MIB::ifName' => 'lo',
        ],
        2 => [
            'IF-MIB::ifIndex' => '2',
            'IF-MIB::ifDescr' => 'enp4s0',
        ],
    ]);
});

test('groupByIndex', function () {
    $response = new SnmpResponse(".1.3.6.1.2.1.2.2.1.10.1 = 495813425\n.1.3.6.1.2.1.2.2.1.10.2 = 3495809228\n");
    expect($response->isValid())->toBeTrue();
    expect($response->groupByIndex())->toBe([1 => ['.1.3.6.1.2.1.2.2.1.10.1' => 495813425], 2 => ['.1.3.6.1.2.1.2.2.1.10.2' => 3495809228]]);
    expect($response->groupByIndex(3))->toBe(['1.10.1' => ['.1.3.6.1.2.1.2.2.1.10.1' => 495813425], '1.10.2' => ['.1.3.6.1.2.1.2.2.1.10.2' => 3495809228]]);
    expect($response->groupByIndex(-2))->toBe(['6.1.2.1.2.2.1.10.1' => ['.1.3.6.1.2.1.2.2.1.10.1' => 495813425], '6.1.2.1.2.2.1.10.2' => ['.1.3.6.1.2.1.2.2.1.10.2' => 3495809228]]);

    $response = new SnmpResponse(".1.3.6.1.2.1.2.2.1.10.1 = 495813425\n.1.3.6.1.2.1.2.2.1.11.1 = 3495809228\n");
    expect($response->groupByIndex())->toBe([1 => ['.1.3.6.1.2.1.2.2.1.10.1' => 495813425, '.1.3.6.1.2.1.2.2.1.11.1' => 3495809228]]);
    expect($response->groupByIndex(2))->toBe(['10.1' => ['.1.3.6.1.2.1.2.2.1.10.1' => 495813425], '11.1' => ['.1.3.6.1.2.1.2.2.1.11.1' => 3495809228]]);
    expect($response->groupByIndex(-5))->toBe(['1.2.2.1.10.1' => ['.1.3.6.1.2.1.2.2.1.10.1' => 495813425], '1.2.2.1.11.1' => ['.1.3.6.1.2.1.2.2.1.11.1' => 3495809228]]);

    $response = new SnmpResponse("SOME-MIB::oid.1.1.0 = 14\nSOME-MIB::oid.1.2.0 = 42\n");
    expect($response->isValid())->toBeTrue();
    expect($response->groupByIndex())->toBe([0 => ['SOME-MIB::oid.1.1.0' => '14', 'SOME-MIB::oid.1.2.0' => '42']]);
    expect($response->groupByIndex(2))->toBe(['1.0' => ['SOME-MIB::oid.1.1.0' => '14'], '2.0' => ['SOME-MIB::oid.1.2.0' => '42']]);
    expect($response->groupByIndex(-1))->toBe(['1.1.0' => ['SOME-MIB::oid.1.1.0' => '14'], '1.2.0' => ['SOME-MIB::oid.1.2.0' => '42']]);
});

test('multiLine', function () {
    $response = new SnmpResponse("SNMPv2-MIB::sysDescr.1 = \"something\n on two lines\"\n");

    expect($response->isValid())->toBeTrue();
    expect($response->value())->toBe("something\n on two lines");
    expect($response->values())->toBe(['SNMPv2-MIB::sysDescr.1' => "something\n on two lines"]);
    expect($response->table())->toBe(['SNMPv2-MIB::sysDescr' => [1 => "something\n on two lines"]]);
});

test('numeric', function () {
    $response = new SnmpResponse(".1.3.6.1.2.1.2.2.1.10.1 = 495813425\n.1.3.6.1.2.1.2.2.1.10.2 = 3495809228\n");

    expect($response->isValid())->toBeTrue();
    expect($response->value())->toBe('496255256');
    expect($response->values())->toBe(['.1.3.6.1.2.1.2.2.1.10.1' => '496255256', '.1.3.6.1.2.1.2.2.1.10.2' => '3495809228']);
    expect($response->table())->toBe(['.1.3.6.1.2.1.2.2.1.10.1' => '496255256', '.1.3.6.1.2.1.2.2.1.10.2' => '3495809228']);
    expect($response->table(3))->toBe(['.1.3.6.1.2.1.2.2.1.10.1' => '496255256', '.1.3.6.1.2.1.2.2.1.10.2' => '3495809228']);
});

test('table', function () {
    $response = new SnmpResponse('HOST-RESOURCES-MIB::hrStorageIndex.1 = 1
HOST-RESOURCES-MIB::hrStorageIndex.34 = 34
HOST-RESOURCES-MIB::hrStorageIndex.36 = 36
HOST-RESOURCES-MIB::hrStorageType.1 = HOST-RESOURCES-TYPES::hrStorageRam
HOST-RESOURCES-MIB::hrStorageType.34 = HOST-RESOURCES-TYPES::hrStorageFixedDisk
HOST-RESOURCES-MIB::hrStorageType.36 = HOST-RESOURCES-TYPES::hrStorageFixedDisk
HOST-RESOURCES-MIB::hrStorageDescr.1 = Physical memory
HOST-RESOURCES-MIB::hrStorageDescr.34 = /run
HOST-RESOURCES-MIB::hrStorageDescr.36 = /
HOST-RESOURCES-MIB::hrStorageAllocationUnits.1 = 1024 Bytes
HOST-RESOURCES-MIB::hrStorageAllocationUnits.34 = 4096 Bytes
HOST-RESOURCES-MIB::hrStorageAllocationUnits.36 = 4096 Bytes
HOST-RESOURCES-MIB::hrStorageSize.1 = 12136128
HOST-RESOURCES-MIB::hrStorageSize.34 = 1517016
HOST-RESOURCES-MIB::hrStorageSize.36 = 193772448
HOST-RESOURCES-MIB::hrStorageUsed.1 = 11577192
HOST-RESOURCES-MIB::hrStorageUsed.34 = 429
HOST-RESOURCES-MIB::hrStorageUsed.36 = 127044934
');

    expect($response->isValid())->toBeTrue();
    expect($response->value())->toBe('34');
    expect($response->values())->toBe([
        'HOST-RESOURCES-MIB::hrStorageIndex.1' => '1',
        'HOST-RESOURCES-MIB::hrStorageIndex.34' => '34',
        'HOST-RESOURCES-MIB::hrStorageIndex.36' => '36',
        'HOST-RESOURCES-MIB::hrStorageType.1' => 'HOST-RESOURCES-TYPES::hrStorageRam',
        'HOST-RESOURCES-MIB::hrStorageType.34' => 'HOST-RESOURCES-TYPES::hrStorageFixedDisk',
        'HOST-RESOURCES-MIB::hrStorageType.36' => 'HOST-RESOURCES-TYPES::hrStorageFixedDisk',
        'HOST-RESOURCES-MIB::hrStorageDescr.1' => 'Physical memory',
        'HOST-RESOURCES-MIB::hrStorageDescr.34' => '/run',
        'HOST-RESOURCES-MIB::hrStorageDescr.36' => '/',
        'HOST-RESOURCES-MIB::hrStorageAllocationUnits.1' => '1024',
        'HOST-RESOURCES-MIB::hrStorageAllocationUnits.34' => '4096',
        'HOST-RESOURCES-MIB::hrStorageAllocationUnits.36' => '4096',
        'HOST-RESOURCES-MIB::hrStorageSize.1' => '12136128',
        'HOST-RESOURCES-MIB::hrStorageSize.34 ' => '1517016',
        'HOST-RESOURCES-MIB::hrStorageSize.36 ' => '193772448',
        'HOST-RESOURCES-MIB::hrStorageUsed.1 =' => '11577192',
        'HOST-RESOURCES-MIB::hrStorageUsed.34 ' => '429',
        'HOST-RESOURCES-MIB::hrStorageUsed.36' => '127044934',
    ]);
    expect($response->table())->toBe([
        '1' => [
            'HOST-RESOURCES-MIB::hrStorageIndex' => '1',
            'HOST-RESOURCES-MIB::hrStorageType' => 'HOST-RESOURCES-TYPES::hrStorageRam',
            'HOST-RESOURCES-MIB::hrStorageDescr' => 'Physical memory',
            'HOST-RESOURCES-MIB::hrStorageAllocationUnits' => '1024',
            'HOST-RESOURCES-MIB::hrStorageSize' => '12136128',
            'HOST-RESOURCES-MIB::hrStorageUsed' => '11577192',
        ],
        '34' => [
            'HOST-RESOURCES-MIB::hrStorageIndex' => '34',
            'HOST-RESOURCES-MIB::hrStorageType' => 'HOST-RESOURCES-TYPES::hrStorageFixedDisk',
            'HOST-RESOURCES-MIB::hrStorageDescr' => '/run',
            'HOST-RESOURCES-MIB::hrStorageAllocationUnits' => '4096',
            'HOST-RESOURCES-MIB::hrStorageSize' => '1517016',
            'HOST-RESOURCES-MIB::hrStorageUsed' => '429',
        ],
        '36' => [
            'HOST-RESOURCES-MIB::hrStorageIndex' => '36',
            'HOST-RESOURCES-MIB::hrStorageType' => 'HOST-RESOURCES-TYPES::hrStorageFixedDisk',
            'HOST-RESOURCES-MIB::hrStorageDescr' => '/',
            'HOST-RESOURCES-MIB::hrStorageAllocationUnits' => '4096',
            'HOST-RESOURCES-MIB::hrStorageSize' => '193772448',
            'HOST-RESOURCES-MIB::hrStorageUsed' => '127044934',
        ],
    ]);
    expect($response->table(1))->toBe([
        'HOST-RESOURCES-MIB::hrStorageIndex' => [
            '1' => '1',
            '34' => '34',
            '36' => '36',
        ],
        'HOST-RESOURCES-MIB::hrStorageType' => [
            '1' => 'HOST-RESOURCES-TYPES::hrStorageRam',
            '34' => 'HOST-RESOURCES-TYPES::hrStorageFixedDisk',
            '36' => 'HOST-RESOURCES-TYPES::hrStorageFixedDisk',
        ],
        'HOST-RESOURCES-MIB::hrStorageDescr' => [
            '1' => 'Physical memory',
            '34' => '/run',
            '36' => '/',
        ],
        'HOST-RESOURCES-MIB::hrStorageAllocationUnits' => [
            '1' => '1024',
            '34' => '4096',
            '36' => '4096',
        ],
        'HOST-RESOURCES-MIB::hrStorageSize' => [
            '1' => '12136128',
            '34' => '1517016',
            '36' => '193772448',
        ],
        'HOST-RESOURCES-MIB::hrStorageUsed' => [
            '1' => '11577192',
            '34' => '429',
            '36' => '127044934',
        ],
    ]);
});

test('trim', function () {
    $response = new SnmpResponse(".1.3.6.1.2.1.2.2.1.10.1 = \\\"4958\\\"\n.1.3.6.1.2.1.2.2.1.10.2 = \"\" 349\r\n\n");
    expect($response->isValid())->toBeTrue();
    expect($response->value())->toBe('4958');
    expect($response->values())->toBe(['.1.3.6.1.2.1.2.2.1.10.1' => '4958', '.1.3.6.1.2.1.2.2.1.10.2' => '349']);

    $response = new SnmpResponse(".1.3.6.1.2.1.31.1.1.1.18.1 = \"internal\\\\backslash\"\n");
    expect($response->isValid())->toBeTrue();
    expect($response->value())->toBe('internal\\backslash');
});

test('error handling', function () {
    // no response
    $response = new SnmpResponse('', "Timeout: No Response from udp:127.1.6.1:1161.\n", 1);
    expect($response->isValid())->toBeFalse();
    expect($response->getErrorMessage())->toBe('Timeout: No Response from udp:127.1.6.1:1161.');

    // correct handling of empty output
    expect($response->value())->toBeEmpty();
    expect($response->values())->toBeEmpty();
    expect($response->table())->toBeEmpty();

    // invalid type (should ignore)
    $response = new SnmpResponse("SNMPv2-MIB::sysObjectID.0 = Wrong Type (should be OBJECT IDENTIFIER): wrong thing\n");
    expect($response->isValid())->toBeTrue();
    expect($response->getErrorMessage())->toBe('');
    expect($response->values())->toBe(['SNMPv2-MIB::sysObjectID.0' => 'wrong thing']);

    // No more variables left in this MIB View
    $response = new SnmpResponse("iso.9 = No more variables left in this MIB View (It is past the end of the MIB tree)\n");
    expect($response->isValid())->toBeFalse();
    expect($response->getErrorMessage())->toBe('No more variables left in this MIB View (It is past the end of the MIB tree)');

    // No Such Instance currently exists at this OID.
    $response = new SnmpResponse("SNMPv2-SMI::enterprises.9.9.661.1.3.2.1.1 = No Such Instance currently exists at this OID.\n");
    expect($response->isValid())->toBeFalse();
    expect($response->getErrorMessage())->toBe('No Such Instance currently exists at this OID.');

    // Unknown user name
    $response = new SnmpResponse('', "snmpget: Unknown user name (Sub-id not found: (top) -> sysDescr)\n", 1);
    expect($response->isValid())->toBeFalse();
    expect($response->getErrorMessage())->toBe('Unknown user name');

    // Authentication failure
    $response = new SnmpResponse('', "snmpget: Authentication failure (incorrect password, community or key) (Sub-id not found: (top) -> sysDescr)\n", 1);
    expect($response->isValid())->toBeFalse();
    expect($response->getErrorMessage())->toBe('Authentication failure');

    // OID not increasing
    $response = new SnmpResponse(".1.3.6.1.2.1.2.2.1.1.1 = INTEGER: 1\n", "Error: OID not increasing: .1.3.6.1.2.100.2.2.1.1\n >= .1.3.6.1.2.1.2.2.1.1.1\n", 1);
    expect($response->isValid())->toBeFalse();
    expect($response->getErrorMessage())->toBe('Error: OID not increasing: .1.3.6.1.2.100.2.2.1.1');

    // NULL return
    $response = new SnmpResponse("hrDeviceTable = NULL\n", '', 0);
    expect($response->isValid())->toBeTrue();
    expect($response->getRawWithoutBadLines())->toBe('');
    $response->mapTable(function (): void {
        throw new Exception('There should be no data in the array.');
    });
});
