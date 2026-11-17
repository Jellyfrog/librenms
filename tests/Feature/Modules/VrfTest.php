<?php

/**
 * VrfTest.php
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

uses(\LibreNMS\Modules\Vrf::class);
use App\Models\Device;
use \LibreNMS\Tests\Feature\Modules\TestVrfModule;
use \LibreNMS\Modules\Vrf;
use \App\Models\Vrf;
use App\Models\Port;
use Illuminate\Support\Collection;
use LibreNMS\Data\Source\Snmp\SnmpQueryBuilder;
use LibreNMS\Data\Source\Snmp\SnmpResponse;
use LibreNMS\Exceptions\SnmpException;
use LibreNMS\Interfaces\Data\DataStorageInterface;
use LibreNMS\Interfaces\Polling\VrfPolling;
use LibreNMS\OS;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    $this->device = Device::factory()->create(['os' => 'generic']);
});

test('rediscovery keeps vrf ids and syncs ports', function () {
    $lan = Port::factory()->create(['device_id' => $this->device->device_id, 'ifIndex' => 10]);
    $wan = Port::factory()->create(['device_id' => $this->device->device_id, 'ifIndex' => 11]);
    $other = Port::factory()->create(['device_id' => $this->device->device_id, 'ifIndex' => 12]);
    $existing = VrfModel::factory()->for($this->device)->create(['vrf_oid' => '3.76.65.78', 'vrf_name' => 'LAN', 'mplsVpnVrfRouteDistinguisher' => '65000:1']);
    $gone = VrfModel::factory()->for($this->device)->create(['vrf_oid' => '4.71.79.78.69', 'vrf_name' => 'GONE']);
    $other->update(['ifVrf' => $gone->vrf_id]);

    discover([
        vrf('3.76.65.78', 'LAN', ['rd' => '65000:10', 'description' => 'Office', 'ifIndexes' => [10, 99]]),
        vrf('3.87.65.78', 'WAN', ['ifIndexes' => [11]]),
    ]);

    $vrfs = $this->device->vrfs()->orderBy('vrf_name')->get()->keyBy('vrf_name');
    expect($vrfs->keys()->all())->toBe(['LAN', 'WAN']);
    expect($vrfs['LAN']->vrf_id)->toBe($existing->vrf_id, 'VRFs are updated in place');
    expect($vrfs['LAN']->mplsVpnVrfRouteDistinguisher)->toBe('65000:10');
    expect($vrfs['LAN']->mplsVpnVrfDescription)->toBe('Office');
    expect($lan->fresh()->ifVrf)->toBe($vrfs['LAN']->vrf_id);
    expect($wan->fresh()->ifVrf)->toBe($vrfs['WAN']->vrf_id);
    expect($other->fresh()->ifVrf)->toBe(0, 'Interfaces of removed VRFs are cleared');

    // the interface moves to another VRF
    discover([
        vrf('3.76.65.78', 'LAN'),
        vrf('3.87.65.78', 'WAN', ['ifIndexes' => [10, 11]]),
    ]);

    expect($lan->fresh()->ifVrf)->toBe($vrfs['WAN']->vrf_id);
    expect($wan->fresh()->ifVrf)->toBe($vrfs['WAN']->vrf_id);
});

test('only contexts that answer are walked', function () {
    $module = discover([
        vrf('1', 'red', ['context' => 'ctx-red']),
        vrf('2', 'blue', ['context' => 'ctx-blue']),
        vrf('3', 'default', ['context' => 'ctx-default']),
    ], ['ctx-red']);

    expect($module->probed)->toBe(['ctx-red', 'ctx-blue'], 'The default VRF context is never walked, so it is not probed');
    expect(contexts())->toBe(['blue' => ['ctx-blue', false], 'default' => [null, false], 'red' => ['ctx-red', true]]);
    expect($this->device->fresh()->getVrfContexts())->toBe(['', 'ctx-red']);
});

test('contexts answer with any object', function () {
    expect(checkContext(new SnmpResponse(['.1.3.6.1.4.1.30065.4.1.1.2.1.13.1.1.4.192.0.2.1' => '6'])))->toBeTrue();
    expect(checkContext(new SnmpResponse(['.1.3.6.1' => 'No more variables left in this MIB View (It is past the end of the MIB tree)'])))->toBeTrue();
    expect(checkContext(new SnmpResponse([], 'Timeout: No Response from udp:192.0.2.1:161', 1)))->toBeFalse();
    expect(checkContext(new SnmpResponse([], 'snmpget: Unknown Context', 1)))->toBeFalse();
});

function checkContext(SnmpResponse $response): bool
{
    $query = Mockery::mock(SnmpQueryBuilder::class);
    $query->shouldReceive('numeric')->andReturnSelf();
    $query->shouldReceive('context')->with('ctx-red')->andReturnSelf();
    $query->shouldReceive('next')->with('.1.3.6.1')->andReturn($response);
    $this->app->instance(SnmpQueryBuilder::class, $query);

    return checkDeviceContext('ctx-red');
}

test('disabled contexts are not checked or walked', function () {
    discover([vrf('1', 'red', ['context' => 'ctx-red']), vrf('2', 'blue', ['context' => 'ctx-blue'])], ['ctx-red', 'ctx-blue']);
    $this->device->vrfs()->where('vrf_name', 'red')->update(['context_enabled' => false]);

    $module = discover([vrf('1', 'red', ['context' => 'ctx-red']), vrf('2', 'blue', ['context' => 'ctx-blue'])], ['ctx-red', 'ctx-blue']);

    expect($module->probed)->toBe(['ctx-blue']);
    expect($this->device->vrfs()->where('vrf_name', 'red')->value('context_enabled'))->toBeFalse('Rediscovery keeps the user setting');
    expect($this->device->fresh()->getVrfContexts())->toBe(['', 'ctx-blue']);
});

test('polling runs discovery without checking contexts', function () {
    $port = Port::factory()->create(['device_id' => $this->device->device_id, 'ifIndex' => 10]);
    discover([vrf('1', 'red', ['context' => 'ctx-red'])], ['ctx-red']);

    $module = discover([vrf('1', 'red', ['context' => 'ctx-red', 'ifIndexes' => [10]])], [], poll: true);

    expect($module->probed)->toBe([], 'Only discovery checks contexts');
    expect(contexts())->toBe(['red' => ['ctx-red', true]], 'Polling keeps the result of discovery');
    expect($port->fresh()->ifVrf)->toBe($this->device->vrfs()->value('vrf_id'));
});

test('vrfs are kept when the device does not answer', function () {
    $port = Port::factory()->create(['device_id' => $this->device->device_id, 'ifIndex' => 10]);
    discover([vrf('1', 'red', ['context' => 'ctx-red', 'ifIndexes' => [10]])], ['ctx-red']);
    $vrfId = $this->device->vrfs()->value('vrf_id');

    $module = discover([], ['ctx-red'], timeout: true);

    expect($module->probed)->toBe([]);
    expect(contexts())->toBe(['red' => ['ctx-red', true]]);
    expect($this->device->vrfs()->value('vrf_id'))->toBe($vrfId);
    expect($port->fresh()->ifVrf)->toBe($vrfId);
});

test('contexts are walked until repeated failures', function () {
    $red = [vrf('1', 'red', ['context' => 'ctx-red'])];
    discover($red, ['ctx-red']);

    foreach (range(1, Vrf::CONTEXT_FAILURE_LIMIT - 1) as $failures) {
        discover($red);
        expect(contexts())->toBe(['red' => ['ctx-red', true]], "Still walked after $failures failures");
        expect($this->device->vrfs()->value('context_failures'))->toBe($failures);
    }

    discover($red);
    expect(contexts())->toBe(['red' => ['ctx-red', false]]);
    expect($this->device->fresh()->getVrfContexts())->toBe(['']);
    expect($this->device->fresh()->getSkippedVrfContexts())->toBe(['ctx-red']);

    discover($red, ['ctx-red']);
    expect(contexts())->toBe(['red' => ['ctx-red', true]], 'One answer is enough');
    expect($this->device->vrfs()->value('context_failures'))->toBe(0);
});

test('changed contexts are checked before they are walked', function () {
    discover([vrf('1', 'red', ['context' => 'ctx-red'])], ['ctx-red']);

    discover([vrf('1', 'red', ['context' => 'ctx-new'])], [], poll: true);
    expect(contexts())->toBe(['red' => ['ctx-new', false]], 'Polling does not trust a new context');

    discover([vrf('1', 'red', ['context' => 'ctx-new'])]);
    expect(contexts())->toBe(['red' => ['ctx-new', false]], 'A new context gets no grace period');

    discover([vrf('1', 'red', ['context' => 'ctx-new'])], ['ctx-new']);
    expect(contexts())->toBe(['red' => ['ctx-new', true]]);
});

test('new vrfs are walked in the same run', function () {
    discover([vrf('1', 'red', ['context' => 'ctx-red'])], ['ctx-red']);

    expect($this->device->getVrfContexts())->toBe(['', 'ctx-red']);
});

test('manual contexts', function () {
    discover([
        vrf('5.86.82.70.45.65', 'VRF-A'),
        vrf('1', 'red', ['context' => 'ctx-red']),
    ], ['ctx-red']);
    setManualContext('VRF-A', 'ctx-a');
    addContextOnlyVrf('vrf-b');
    addContextOnlyVrf('missing');

    $module = discover([
        vrf('5.86.82.70.45.65', 'VRF-A'),
        vrf('1', 'red', ['context' => 'ctx-red']),
    ], ['ctx-a', 'vrf-b', 'ctx-red']);

    expect($module->probed)->toBe(['ctx-a', 'ctx-red', 'vrf-b', 'missing']);
    expect(contexts())->toBe(['VRF-A' => ['ctx-a', true], 'missing' => ['missing', false], 'red' => ['ctx-red', true], 'vrf-b' => ['vrf-b', true]]);
    expect(manualFlags())->toBe(['VRF-A' => [true, false], 'missing' => [true, true], 'red' => [false, false], 'vrf-b' => [true, true]], 'Manual contexts and VRFs only known through their context are kept');

    $device = $this->device->fresh();
    expect($device->getVrfContexts())->toEqualCanonicalizing(['', 'ctx-a', 'ctx-red', 'vrf-b']);
    expect($device->vrfIdForContext('ctx-a'))->toBe($device->vrfs->firstWhere('vrf_name', 'VRF-A')->vrf_id);
    expect($device->vrfIdForContext('ctx-red'))->toBe($device->vrfs->firstWhere('vrf_name', 'red')->vrf_id);
    expect($device->vrfIdForContext('red'))->toBe($device->vrfs->firstWhere('vrf_name', 'red')->vrf_id, 'VRF names stored as context resolve');
    expect($device->vrfIdForContext(''))->toBeNull();
});

test('manual context follows its vrf', function () {
    discover([vrf('2', 'blue')]);
    setManualContext(null, 'ctx-blue');

    discover([vrf('2', 'BLUE')], ['ctx-blue']);
    expect(contexts())->toBe(['BLUE' => ['ctx-blue', true]], 'Renamed VRFs keep their context');

    discover([vrf('7', 'BLUE')], ['ctx-blue']);
    expect(contexts())->toBe(['BLUE' => ['ctx-blue', true]], 'Renumbered VRFs keep their context');
    expect($this->device->vrfs()->sole()->vrf_oid)->toBe('7');

    // the device stops listing the VRF, it is kept through its context until it is listed again
    discover([vrf('8', 'green')], ['ctx-blue']);
    expect(contexts())->toBe(['BLUE' => ['ctx-blue', true], 'green' => [null, false]]);
    $kept = $this->device->vrfs()->firstWhere('vrf_name', 'BLUE');
    expect([$kept->vrf_oid, $kept->context_manual, $kept->vrf_manual])->toBe(['context:ctx-blue', true, true]);

    discover([vrf('8', 'green'), vrf('9', 'blue')], ['ctx-blue']);
    $blue = $this->device->vrfs()->firstWhere('vrf_name', 'blue');
    expect([$blue->vrf_oid, $blue->context_name, $blue->context_manual, $blue->vrf_manual])->toBe(['9', 'ctx-blue', true, false]);
    expect($this->device->vrfs()->count())->toBe(2);
});

test('manual context follows its vrf when indexes are reused', function () {
    discover([vrf('1', 'A'), vrf('2', 'B')]);
    setManualContext('A', 'ctx-a');

    discover([vrf('1', 'B'), vrf('3', 'A')], ['ctx-a']);

    expect(contexts())->toBe(['A' => ['ctx-a', true], 'B' => [null, false]]);
});

test('vrfs only known through their context do not replace listed vrfs', function () {
    addContextOnlyVrf('2');

    discover([vrf('2', 'blue', ['rd' => '65000:2'])], ['2']);

    expect(contexts())->toBe(['2' => ['2', true], 'blue' => [null, false]]);
    expect($this->device->vrfs()->firstWhere('vrf_oid', '2')->mplsVpnVrfRouteDistinguisher)->toBe('65000:2');
});

test('polling keeps manual contexts of polled vrfs', function () {
    discover([vrf('2', 'blue')]);
    setManualContext(null, 'ctx-blue');
    addContextOnlyVrf('red');
    $this->device->unsetRelation('vrfs');

    // VrfPolling gets the saved VRFs, including the ones only known through their context
    $os = Mockery::mock(OS::class, VrfPolling::class);
    $os->shouldReceive('getDevice')->andReturn($this->device);
    $os->shouldReceive('pollVrfs')->andReturnUsing(fn (Collection $vrfs) => $vrfs);
    $os->shouldReceive('discoverVrfPorts')->andReturn(new Collection);

    /** @var OS $os */
    new TestVrfModule([])->poll($os, Mockery::mock(DataStorageInterface::class));

    expect(manualFlags())->toBe(['blue' => [true, false], 'red' => [true, true]]);
    expect(contexts())->toBe(['blue' => ['ctx-blue', false], 'red' => ['red', false]]);
});

test('device contexts win over manual contexts', function () {
    discover([vrf('2', 'blue'), vrf('3', 'green')]);
    setManualContext('blue', 'ctx-blue');
    setManualContext('green', 'ctx-green');

    discover([vrf('2', 'blue', ['context' => 'ctx-device']), vrf('3', 'green'), vrf('4', 'red', ['context' => 'ctx-green'])], ['ctx-device', 'ctx-green']);

    expect(contexts())->toBe(['blue' => ['ctx-device', true], 'green' => [null, false], 'red' => ['ctx-green', true]]);
    expect(manualFlags())->toBe(['blue' => [false, false], 'green' => [false, false], 'red' => [false, false]]);
});

test('manual vrf moves to the vrf the device lists', function () {
    addContextOnlyVrf('blue');

    discover([vrf('2', 'Blue')], ['blue']);

    $vrf = $this->device->vrfs()->sole();
    expect([$vrf->vrf_oid, $vrf->vrf_name, $vrf->context_name, $vrf->context_manual, $vrf->vrf_manual])->toBe(['2', 'Blue', 'blue', true, false]);

    // the device maps its own context later
    discover([vrf('2', 'Blue', ['context' => 'ctx-blue'])], ['ctx-blue']);

    $vrf = $this->device->vrfs()->sole();
    expect([$vrf->vrf_oid, $vrf->context_name, $vrf->context_manual, $vrf->vrf_manual])->toBe(['2', 'ctx-blue', false, false]);
});

test('cleanup', function () {
    $vrf = VrfModel::factory()->for($this->device)->create();
    $port = Port::factory()->create(['device_id' => $this->device->device_id, 'ifVrf' => $vrf->vrf_id]);
    $module = new Vrf;

    expect($module->dataExists($this->device))->toBeTrue();
    expect($module->cleanup($this->device))->toBe(1);
    expect($module->dataExists($this->device))->toBeFalse();
    expect($port->fresh()->ifVrf)->toBe(0);
});

test('dump', function () {
    $vrf = VrfModel::factory()->for($this->device)->create(['vrf_oid' => '2', 'vrf_name' => 'vprn2', 'context_name' => null, 'bgpLocalAs' => 65000, 'mplsVpnVrfRouteDistinguisher' => '65000:2', 'mplsVpnVrfDescription' => '']);
    Port::factory()->create(['device_id' => $this->device->device_id, 'ifIndex' => 7, 'ifVrf' => $vrf->vrf_id]);
    Port::factory()->create(['device_id' => $this->device->device_id, 'ifIndex' => 3, 'ifVrf' => $vrf->vrf_id]);

    expect((new Vrf)->dump($this->device, 'discovery')['vrfs'])->toBe([[
        'vrf_oid' => '2',
        'vrf_name' => 'vprn2',
        'context_name' => null,
        'context_verified' => false,
        'bgpLocalAs' => 65000,
        'mplsVpnVrfRouteDistinguisher' => '65000:2',
        'mplsVpnVrfDescription' => '',
        'ifIndices' => '3,7',
    ]]);
});

/**
 * Set a context like users do under Device > Edit > Routing, on the VRF with this name or the only VRF
 */
function setManualContext(?string $vrfName, string $context): void
{
    $vrf = $vrfName === null ? $this->device->vrfs()->sole() : $this->device->vrfs()->firstWhere('vrf_name', $vrfName);
    $vrf->fill(['context_name' => $context, 'context_manual' => true])->save();
}

function addContextOnlyVrf(string $context): void
{
    $this->device->vrfs()->create([
        'vrf_oid' => VrfModel::contextOnlyOid($context),
        'vrf_name' => $context,
        'context_name' => $context,
        'context_manual' => true,
        'vrf_manual' => true,
        'mplsVpnVrfDescription' => '',
    ]);
    $this->device->unsetRelation('vrfs');
}

/**
 * @return array<string, array{0: bool, 1: bool}>
 */
function manualFlags(): array
{
    return $this->device->vrfs()->get()->sortBy('vrf_name', SORT_STRING)
        ->mapWithKeys(fn (VrfModel $vrf) => [$vrf->vrf_name => [$vrf->context_manual, $vrf->vrf_manual]])->all();
}

/**
 * @return array<string, array{0: string|null, 1: bool}>
 */
function contexts(): array
{
    return $this->device->vrfs()->get()->sortBy('vrf_name', SORT_STRING)
        ->mapWithKeys(fn (VrfModel $vrf) => [$vrf->vrf_name => [$vrf->context_name, $vrf->context_verified]])->all();
}

/**
 * @param  VrfSpec[]  $vrfs
 * @param  string[]  $answering  contexts that answer
 * @param  bool  $timeout  the device does not answer VRF discovery
 */
function discover(array $vrfs, array $answering = [], bool $poll = false, bool $timeout = false): TestVrfModule
{
    $os = Mockery::mock(OS::class);
    $os->shouldReceive('getDevice')->andReturn($this->device);
    $os->shouldReceive('discoverVrfs')->andReturnUsing(function () use ($vrfs, $timeout) {
        if ($timeout) {
            throw new SnmpException('Timeout: No Response from udp:192.0.2.1:161');
        }

        return collect($vrfs)->map(fn (array $vrf) => new VrfModel([
            'vrf_oid' => $vrf['vrf_oid'],
            'vrf_name' => $vrf['name'],
            'context_name' => $vrf['context'],
            'mplsVpnVrfRouteDistinguisher' => $vrf['rd'],
            'mplsVpnVrfDescription' => $vrf['description'],
        ]));
    });
    $os->shouldReceive('discoverVrfPorts')->andReturnUsing(function (Collection $saved) use ($vrfs) {
        $ports = new Collection;
        foreach ($vrfs as $vrf) {
            foreach ($vrf['ifIndexes'] as $ifIndex) {
                $port = $this->device->ports()->firstWhere('ifIndex', $ifIndex);
                if ($port) {
                    $port->ifVrf = $saved->firstWhere('vrf_oid', $vrf['vrf_oid'])->vrf_id;
                    $ports->push($port);
                }
            }
        }

        return $ports;
    });

    $module = new TestVrfModule($answering);

    /** @var OS $os */
    if ($poll) {
        $module->poll($os, Mockery::mock(DataStorageInterface::class));
    } else {
        discover($os);
    }

    return $module;
}

/**
 * @param  array<string, mixed>  $fields
 * @return VrfSpec
 */
function vrf(string $vrfOid, string $name, array $fields = []): array
{
    return array_merge(['vrf_oid' => $vrfOid, 'name' => $name, 'rd' => null, 'description' => '', 'context' => null, 'ifIndexes' => []], $fields);
}

class TestVrfModule extends Vrf
{
    /**
     * @param  string[]  $answering
     */
    function __construct(private readonly array $answering)
    {
    }

    function checkContext(string $context): bool
    {
        $this->probed[] = $context;

        return in_array($context, $this->answering, true);
    }

    function checkDeviceContext(string $context): bool
    {
        return checkContext($context);
    }
}