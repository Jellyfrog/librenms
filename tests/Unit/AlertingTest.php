<?php

/**
 * AlertingTest.php
 *
 * Tests for alerting functionality.
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
 * @copyright  2016 Neil Lathwood
 * @author     Neil Lathwood <neil@lathwood.co.uk>
 */

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\AlertRule;
use LibreNMS\Alert\AlertUtil;

test('json alert collection', function () {
    $rules = get_rules_from_json();
    expect($rules)->toBeArray();
    foreach ($rules as $rule) {
        expect($rule)->toBeArray();
    }
});

test('extract id fields for fault', function () {
    $fields = AlertUtil::extractIdFieldsForFault([
        'id' => 9,
        'port_id' => 5,
        'device_id' => 1,
        'location_id' => 2,
        'ifName' => 'eth0',
    ]);

    expect($fields)->toContain('id');
    expect($fields)->toContain('port_id');
    expect($fields)->toContain('device_id');

    // location_id is intentionally excluded, and non-id fields are not considered.
    expect($fields)->not->toContain('location_id');
    expect($fields)->not->toContain('ifName');
});

test('generate comparison key for fault', function () {
    $row = ['device_id' => 1, 'port_id' => 5, 'ifName' => 'eth0'];
    $key = AlertUtil::generateComparisonKeyForFault($row, AlertUtil::extractIdFieldsForFault($row));

    // The same entity always yields the same dedupe key, distinct from other ports.
    expect($key)->toBe('1|5');
    $this->assertNotSame($key, AlertUtil::generateComparisonKeyForFault(
        ['device_id' => 1, 'port_id' => 6],
        AlertUtil::extractIdFieldsForFault(['device_id' => 1, 'port_id' => 6])
    ));
});

test('fault key for row uses sensor identity', function () {
    $row = [
        'device_id' => 1,
        'sensor_id' => 9,
        'sensor_class' => 'temperature',
        'sensor_type' => 'routeros',
        'sensor_index' => '3',
    ];

    expect(AlertUtil::faultKeyForRow($row))->toBe('sensor|1||temperature|routeros|3');
    expect(AlertUtil::faultKeyForRow(array_merge($row, ['sensor_id' => 99])))->toBe(AlertUtil::faultKeyForRow($row));
});

test('fault key for row uses processor identity', function () {
    $row = [
        'device_id' => 1,
        'processor_id' => 4,
        'processor_type' => 'hr',
        'processor_index' => '2',
    ];

    expect(AlertUtil::faultKeyForRow($row))->toBe('processor|1|hr|2');
    expect(AlertUtil::faultKeyForRow(array_merge($row, ['processor_id' => 88])))->toBe(AlertUtil::faultKeyForRow($row));
});

test('fault key for row uses bgp peer identity', function () {
    $row = [
        'device_id' => 1,
        'context_name' => '',
        'bgpPeerIdentifier' => '10.0.0.1',
        'bgpPeer_id' => 7,
    ];

    expect(AlertUtil::faultKeyForRow($row))->toBe('bgppeer|1||10.0.0.1');
    expect(AlertUtil::faultKeyForRow(array_merge($row, ['bgpPeer_id' => 99])))->toBe(AlertUtil::faultKeyForRow($row));
});

test('entity for fault', function () {
    // Known columns map to the registered morph aliases.
    expect(AlertUtil::entityForFault(['device_id' => 1, 'port_id' => 5]))->toBe(['interface', 5]);
    expect(AlertUtil::entityForFault(['device_id' => 1, 'sensor_id' => 9]))->toBe(['sensor', 9]);
    expect(AlertUtil::entityForFault([
        'device_id' => 1,
        'processor_type' => 'hr',
        'processor_index' => '2',
        'processor_id' => 4,
    ]))->toBe(['processor', 4]);
    expect(AlertUtil::entityForFault(['device_id' => 1, 'bgpPeer_id' => 7]))->toBe(['bgppeer', 7]);
    expect(AlertUtil::entityForFault([
        'device_id' => 1,
        'context_name' => '',
        'bgpPeerIdentifier' => '10.0.0.1',
        'bgpPeer_id' => 7,
    ]))->toBe(['bgppeer', 7]);

    // Unknown *_id columns fall back to the stripped column name.
    expect(AlertUtil::entityForFault(['device_id' => 1, 'customThing_id' => 3]))->toBe(['customThing', 3]);

    // A device-level row (no entity id) resolves to no specific entity.
    expect(AlertUtil::entityForFault(['device_id' => 1, 'ifName' => 'eth0']))->toBe([null, null]);
});

test('should notify per entity respects max entities', function () {
    $rule = new AlertRule([
        'notify_per_entity' => true,
        'max_entities' => 10,
    ]);

    expect(AlertUtil::shouldNotifyPerEntity($rule, 10))->toBeTrue();
    expect(AlertUtil::shouldNotifyPerEntity($rule, 11))->toBeFalse();
    expect(AlertUtil::shouldNotifyPerEntity(new AlertRule(['notify_per_entity' => false, 'max_entities' => 10]), 5))->toBeFalse();
});

test('transports', function () {
    foreach (getTransportFiles() as $file => $_unused) {
        $parts = explode('/', (string) $file);
        $transport = ucfirst(str_replace('.php', '', array_pop($parts)));
        $class = 'LibreNMS\\Alert\\Transport\\' . $transport;
        expect(class_exists($class))->toBeTrue("The transport $transport does not exist");
        expect(new $class)->toBeInstanceOf(\LibreNMS\Interfaces\Alert\Transport::class);
    }
});

function getTransportFiles(): RegexIterator
{
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('LibreNMS/Alert/Transport'));

    return new RegexIterator($iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH);
}
