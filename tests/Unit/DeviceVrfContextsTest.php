<?php

/**
 * DeviceVrfContextsTest.php
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
 * @copyright  2026 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use App\Models\Device;
use App\Models\Vrf;
use Illuminate\Database\Eloquent\Collection;

uses(LibreNMS\Tests\TestCase::class);

test('default context only without vrfs', function (): void {
    expect(vrfDevice([])->getVrfContexts())->toBe(['']);
});

test('vrf contexts', function (): void {
    $device = vrfDevice([
        [1, 'red', 'ctx-red'],
        [2, 'blue', null],
        [3, 'Default', 'default'],
        [4, 'green', 'ctx-red'],
        [5, 'yellow', 'ctx-yellow'],
        [6, 'purple', 'ctx-purple', false],
        [7, 'orange', 'ctx-orange', true, false],
    ]);

    expect($device->getVrfContexts())->toBe(['', 'ctx-red', 'ctx-yellow'], 'The default VRF context, duplicates, contexts that did not answer and disabled contexts are skipped');
    expect($device->getSkippedVrfContexts())->toBe(['ctx-purple', 'ctx-orange'], 'Contexts that did not answer and disabled contexts are kept separately');
});

test('vrf id for context', function (): void {
    $device = vrfDevice([
        [1, 'red', 'ctx-red'],
        [2, 'blue', null],
        [3, 'ctx-red', null],
    ]);

    expect($device->vrfIdForContext(''))->toBeNull();
    expect($device->vrfIdForContext(null))->toBeNull();
    expect($device->vrfIdForContext('ctx-red'))->toBe(1, 'Contexts are matched before names');
    expect($device->vrfIdForContext('blue'))->toBe(2);
    expect($device->vrfIdForContext('green'))->toBeNull();
});

/**
 * @param  array<int, array{0: int, 1: string, 2: string|null, 3?: bool, 4?: bool}>  $vrfs  id, name, context, verified, enabled
 */
function vrfDevice(array $vrfs): Device
{
    $device = new Device;
    $device->setRelation('vrfs', new Collection(array_map(fn (array $vrf) => (new Vrf)->forceFill([
        'vrf_id' => $vrf[0],
        'vrf_name' => $vrf[1],
        'context_name' => $vrf[2],
        'context_verified' => $vrf[3] ?? true,
        'context_enabled' => $vrf[4] ?? true,
    ]), $vrfs)));

    return $device;
}
