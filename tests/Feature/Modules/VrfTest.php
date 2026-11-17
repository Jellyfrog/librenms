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

use App\Models\Device;
use App\Models\Port;
use App\Models\Vrf as VrfModel;
use Illuminate\Support\Collection;
use LibreNMS\Data\Source\Snmp\SnmpQueryBuilder;
use LibreNMS\Data\Source\Snmp\SnmpResponse;
use LibreNMS\Exceptions\SnmpException;
use LibreNMS\Interfaces\Data\DataStorageInterface;
use LibreNMS\Interfaces\Polling\VrfPolling;
use LibreNMS\Modules\Vrf;
use LibreNMS\OS;

uses(LibreNMS\Tests\DBTestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function (): void {
    $this->device = Device::factory()->create(['os' => 'generic']);
});

test('rediscovery keeps vrf ids and syncs ports', function (): void {
    $lan = Port::factory()->create(['device_id' => $this->device->device_id, 'ifIndex' => 10]);
    $wan = Port::factory()->create(['device_id' => $this->device->device_id, 'ifIndex' => 11]);
    $other = Port::factory()->create(['device_id' => $this->device->device_id, 'ifIndex' => 12]);
    $existing = VrfModel::factory()->for($this->device)->create(['vrf_oid' => '3.76.65.78', 'vrf_name' => 'LAN', 'mplsVpnVrfRouteDistinguisher' => '65000:1']);
    $gone = VrfModel::factory()->for($this->device)->create(['vrf_oid' => '4.71.79.78.69', 'vrf_name' => 'GONE']);
    $other->update(['ifVrf' => $gone->vrf_id]);

    runVrfModule([
        vrfSpec('3.76.65.78', 'LAN', ['rd' => '65000:10', 'description' => 'Office', 'ifIndexes' => [10, 99]]),
        vrfSpec('3.87.65.78', 'WAN', ['ifIndexes' => [11]]),
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
    runVrfModule([
        vrfSpec('3.76.65.78', 'LAN'),
        vrfSpec('3.87.65.78', 'WAN', ['ifIndexes' => [10, 11]]),
    ]);

    expect($lan->fresh()->ifVrf)->toBe($vrfs['WAN']->vrf_id);
    expect($wan->fresh()->ifVrf)->toBe($vrfs['WAN']->vrf_id);
});

test('only contexts that answer are walked', function (): void {
    $module = runVrfModule([
        vrfSpec('1', 'red', ['context' => 'ctx-red']),
        vrfSpec('2', 'blue', ['context' => 'ctx-blue']),
        vrfSpec('3', 'default', ['context' => 'ctx-default']),
    ], ['ctx-red']);

    expect($module->probed)->toBe(['ctx-red', 'ctx-blue'], 'The default VRF context is never walked, so it is not probed');
    expect(vrfContexts())->toBe(['blue' => ['ctx-blue', false], 'default' => [null, false], 'red' => ['ctx-red', true]]);
    expect($this->device->fresh()->getVrfContexts())->toBe(['', 'ctx-red']);
});

test('contexts answer with any object', function (): void {
    expect(checkVrfContext(new SnmpResponse(['.1.3.6.1.4.1.30065.4.1.1.2.1.13.1.1.4.192.0.2.1' => '6'])))->toBeTrue();
    expect(checkVrfContext(new SnmpResponse(['.1.3.6.1' => 'No more variables left in this MIB View (It is past the end of the MIB tree)'])))->toBeTrue();
    expect(checkVrfContext(new SnmpResponse([], 'Timeout: No Response from udp:192.0.2.1:161', 1)))->toBeFalse();
    expect(checkVrfContext(new SnmpResponse([], 'snmpget: Unknown Context', 1)))->toBeFalse();
});

function checkVrfContext(SnmpResponse $response): bool
{
    $query = Mockery::mock(SnmpQueryBuilder::class);
    $query->shouldReceive('numeric')->andReturnSelf();
    $query->shouldReceive('context')->with('ctx-red')->andReturnSelf();
    $query->shouldReceive('next')->with('.1.3.6.1')->andReturn($response);
    app()->instance(SnmpQueryBuilder::class, $query);

    return new TestVrfModule([])->checkDeviceContext('ctx-red');
}

test('disabled contexts are not checked or walked', function (): void {
    runVrfModule([vrfSpec('1', 'red', ['context' => 'ctx-red']), vrfSpec('2', 'blue', ['context' => 'ctx-blue'])], ['ctx-red', 'ctx-blue']);
    $this->device->vrfs()->where('vrf_name', 'red')->update(['context_enabled' => false]);

    $module = runVrfModule([vrfSpec('1', 'red', ['context' => 'ctx-red']), vrfSpec('2', 'blue', ['context' => 'ctx-blue'])], ['ctx-red', 'ctx-blue']);

    expect($module->probed)->toBe(['ctx-blue']);
    expect($this->device->vrfs()->where('vrf_name', 'red')->value('context_enabled'))->toBeFalse('Rediscovery keeps the user setting');
    expect($this->device->fresh()->getVrfContexts())->toBe(['', 'ctx-blue']);
});

test('polling runs discovery without checking contexts', function (): void {
    $port = Port::factory()->create(['device_id' => $this->device->device_id, 'ifIndex' => 10]);
    runVrfModule([vrfSpec('1', 'red', ['context' => 'ctx-red'])], ['ctx-red']);

    $module = runVrfModule([vrfSpec('1', 'red', ['context' => 'ctx-red', 'ifIndexes' => [10]])], [], poll: true);

    expect($module->probed)->toBe([], 'Only discovery checks contexts');
    expect(vrfContexts())->toBe(['red' => ['ctx-red', true]], 'Polling keeps the result of discovery');
    expect($port->fresh()->ifVrf)->toBe($this->device->vrfs()->value('vrf_id'));
});

test('vrfs are kept when the device does not answer', function (): void {
    $port = Port::factory()->create(['device_id' => $this->device->device_id, 'ifIndex' => 10]);
    runVrfModule([vrfSpec('1', 'red', ['context' => 'ctx-red', 'ifIndexes' => [10]])], ['ctx-red']);
    $vrfId = $this->device->vrfs()->value('vrf_id');

    $module = runVrfModule([], ['ctx-red'], timeout: true);

    expect($module->probed)->toBe([]);
    expect(vrfContexts())->toBe(['red' => ['ctx-red', true]]);
    expect($this->device->vrfs()->value('vrf_id'))->toBe($vrfId);
    expect($port->fresh()->ifVrf)->toBe($vrfId);
});

test('contexts are walked until repeated failures', function (): void {
    $red = [vrfSpec('1', 'red', ['context' => 'ctx-red'])];
    runVrfModule($red, ['ctx-red']);

    foreach (range(1, Vrf::CONTEXT_FAILURE_LIMIT - 1) as $failures) {
        runVrfModule($red);
        expect(vrfContexts())->toBe(['red' => ['ctx-red', true]], "Still walked after $failures failures");
        expect($this->device->vrfs()->value('context_failures'))->toBe($failures);
    }

    runVrfModule($red);
    expect(vrfContexts())->toBe(['red' => ['ctx-red', false]]);
    expect($this->device->fresh()->getVrfContexts())->toBe(['']);
    expect($this->device->fresh()->getSkippedVrfContexts())->toBe(['ctx-red']);

    runVrfModule($red, ['ctx-red']);
    expect(vrfContexts())->toBe(['red' => ['ctx-red', true]], 'One answer is enough');
    expect($this->device->vrfs()->value('context_failures'))->toBe(0);
});

test('changed contexts are checked before they are walked', function (): void {
    runVrfModule([vrfSpec('1', 'red', ['context' => 'ctx-red'])], ['ctx-red']);

    runVrfModule([vrfSpec('1', 'red', ['context' => 'ctx-new'])], [], poll: true);
    expect(vrfContexts())->toBe(['red' => ['ctx-new', false]], 'Polling does not trust a new context');

    runVrfModule([vrfSpec('1', 'red', ['context' => 'ctx-new'])]);
    expect(vrfContexts())->toBe(['red' => ['ctx-new', false]], 'A new context gets no grace period');

    runVrfModule([vrfSpec('1', 'red', ['context' => 'ctx-new'])], ['ctx-new']);
    expect(vrfContexts())->toBe(['red' => ['ctx-new', true]]);
});

test('new vrfs are walked in the same run', function (): void {
    runVrfModule([vrfSpec('1', 'red', ['context' => 'ctx-red'])], ['ctx-red']);

    expect($this->device->getVrfContexts())->toBe(['', 'ctx-red']);
});

test('manual contexts', function (): void {
    runVrfModule([
        vrfSpec('5.86.82.70.45.65', 'VRF-A'),
        vrfSpec('1', 'red', ['context' => 'ctx-red']),
    ], ['ctx-red']);
    setManualVrfContext('VRF-A', 'ctx-a');
    addContextOnlyVrf('vrf-b');
    addContextOnlyVrf('missing');

    $module = runVrfModule([
        vrfSpec('5.86.82.70.45.65', 'VRF-A'),
        vrfSpec('1', 'red', ['context' => 'ctx-red']),
    ], ['ctx-a', 'vrf-b', 'ctx-red']);

    expect($module->probed)->toBe(['ctx-a', 'ctx-red', 'vrf-b', 'missing']);
    expect(vrfContexts())->toBe(['VRF-A' => ['ctx-a', true], 'missing' => ['missing', false], 'red' => ['ctx-red', true], 'vrf-b' => ['vrf-b', true]]);
    expect(vrfManualFlags())->toBe(
        ['VRF-A' => [true, false], 'missing' => [true, true], 'red' => [false, false], 'vrf-b' => [true, true]],
        'Manual contexts and VRFs only known through their context are kept',
    );

    $device = $this->device->fresh();
    expect($device->getVrfContexts())->toEqualCanonicalizing(['', 'ctx-a', 'ctx-red', 'vrf-b']);
    expect($device->vrfIdForContext('ctx-a'))->toBe($device->vrfs->firstWhere('vrf_name', 'VRF-A')->vrf_id);
    expect($device->vrfIdForContext('ctx-red'))->toBe($device->vrfs->firstWhere('vrf_name', 'red')->vrf_id);
    expect($device->vrfIdForContext('red'))->toBe($device->vrfs->firstWhere('vrf_name', 'red')->vrf_id, 'VRF names stored as context resolve');
    expect($device->vrfIdForContext(''))->toBeNull();
});

test('manual context follows its vrf', function (): void {
    runVrfModule([vrfSpec('2', 'blue')]);
    setManualVrfContext(null, 'ctx-blue');

    runVrfModule([vrfSpec('2', 'BLUE')], ['ctx-blue']);
    expect(vrfContexts())->toBe(['BLUE' => ['ctx-blue', true]], 'Renamed VRFs keep their context');

    runVrfModule([vrfSpec('7', 'BLUE')], ['ctx-blue']);
    expect(vrfContexts())->toBe(['BLUE' => ['ctx-blue', true]], 'Renumbered VRFs keep their context');
    expect($this->device->vrfs()->sole()->vrf_oid)->toBe('7');

    // the device stops listing the VRF, it is kept through its context until it is listed again
    runVrfModule([vrfSpec('8', 'green')], ['ctx-blue']);
    expect(vrfContexts())->toBe(['BLUE' => ['ctx-blue', true], 'green' => [null, false]]);
    $kept = $this->device->vrfs()->firstWhere('vrf_name', 'BLUE');
    expect([$kept->vrf_oid, $kept->context_manual, $kept->vrf_manual])->toBe(['context:ctx-blue', true, true]);

    runVrfModule([vrfSpec('8', 'green'), vrfSpec('9', 'blue')], ['ctx-blue']);
    $blue = $this->device->vrfs()->firstWhere('vrf_name', 'blue');
    expect([$blue->vrf_oid, $blue->context_name, $blue->context_manual, $blue->vrf_manual])->toBe(['9', 'ctx-blue', true, false]);
    expect($this->device->vrfs()->count())->toBe(2);
});

test('manual context follows its vrf when indexes are reused', function (): void {
    runVrfModule([vrfSpec('1', 'A'), vrfSpec('2', 'B')]);
    setManualVrfContext('A', 'ctx-a');

    runVrfModule([vrfSpec('1', 'B'), vrfSpec('3', 'A')], ['ctx-a']);

    expect(vrfContexts())->toBe(['A' => ['ctx-a', true], 'B' => [null, false]]);
});

test('vrfs only known through their context do not replace listed vrfs', function (): void {
    addContextOnlyVrf('2');

    runVrfModule([vrfSpec('2', 'blue', ['rd' => '65000:2'])], ['2']);

    expect(vrfContexts())->toBe(['2' => ['2', true], 'blue' => [null, false]]);
    expect($this->device->vrfs()->firstWhere('vrf_oid', '2')->mplsVpnVrfRouteDistinguisher)->toBe('65000:2');
});

test('polling keeps manual contexts of polled vrfs', function (): void {
    runVrfModule([vrfSpec('2', 'blue')]);
    setManualVrfContext(null, 'ctx-blue');
    addContextOnlyVrf('red');
    $this->device->unsetRelation('vrfs');

    // VrfPolling gets the saved VRFs, including the ones only known through their context
    $os = Mockery::mock(OS::class, VrfPolling::class);
    $os->shouldReceive('getDevice')->andReturn($this->device);
    $os->shouldReceive('pollVrfs')->andReturnUsing(fn (Collection $vrfs) => $vrfs);
    $os->shouldReceive('discoverVrfPorts')->andReturn(new Collection);
    /** @var OS $os */
    new TestVrfModule([])->poll($os, Mockery::mock(DataStorageInterface::class));

    expect(vrfManualFlags())->toBe(['blue' => [true, false], 'red' => [true, true]]);
    expect(vrfContexts())->toBe(['blue' => ['ctx-blue', false], 'red' => ['red', false]]);
});

test('device contexts win over manual contexts', function (): void {
    runVrfModule([vrfSpec('2', 'blue'), vrfSpec('3', 'green')]);
    setManualVrfContext('blue', 'ctx-blue');
    setManualVrfContext('green', 'ctx-green');

    runVrfModule([vrfSpec('2', 'blue', ['context' => 'ctx-device']), vrfSpec('3', 'green'), vrfSpec('4', 'red', ['context' => 'ctx-green'])], ['ctx-device', 'ctx-green']);

    expect(vrfContexts())->toBe(['blue' => ['ctx-device', true], 'green' => [null, false], 'red' => ['ctx-green', true]]);
    expect(vrfManualFlags())->toBe(['blue' => [false, false], 'green' => [false, false], 'red' => [false, false]]);
});

test('manual vrf moves to the vrf the device lists', function (): void {
    addContextOnlyVrf('blue');

    runVrfModule([vrfSpec('2', 'Blue')], ['blue']);

    $vrf = $this->device->vrfs()->sole();
    expect([$vrf->vrf_oid, $vrf->vrf_name, $vrf->context_name, $vrf->context_manual, $vrf->vrf_manual])->toBe(['2', 'Blue', 'blue', true, false]);

    // the device maps its own context later
    runVrfModule([vrfSpec('2', 'Blue', ['context' => 'ctx-blue'])], ['ctx-blue']);

    $vrf = $this->device->vrfs()->sole();
    expect([$vrf->vrf_oid, $vrf->context_name, $vrf->context_manual, $vrf->vrf_manual])->toBe(['2', 'ctx-blue', false, false]);
});

test('cleanup', function (): void {
    $vrf = VrfModel::factory()->for($this->device)->create();
    $port = Port::factory()->create(['device_id' => $this->device->device_id, 'ifVrf' => $vrf->vrf_id]);
    $module = new Vrf;

    expect($module->dataExists($this->device))->toBeTrue();
    expect($module->cleanup($this->device))->toBe(1);
    expect($module->dataExists($this->device))->toBeFalse();
    expect($port->fresh()->ifVrf)->toBe(0);
});

test('dump', function (): void {
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
function setManualVrfContext(?string $vrfName, string $context): void
{
    $vrf = $vrfName === null ? test()->device->vrfs()->sole() : test()->device->vrfs()->firstWhere('vrf_name', $vrfName);
    $vrf->fill(['context_name' => $context, 'context_manual' => true])->save();
}

function addContextOnlyVrf(string $context): void
{
    test()->device->vrfs()->create([
        'vrf_oid' => VrfModel::contextOnlyOid($context),
        'vrf_name' => $context,
        'context_name' => $context,
        'context_manual' => true,
        'vrf_manual' => true,
        'mplsVpnVrfDescription' => '',
    ]);
    test()->device->unsetRelation('vrfs');
}

/**
 * @return array<string, array{0: bool, 1: bool}>
 */
function vrfManualFlags(): array
{
    return test()->device->vrfs()->get()->sortBy('vrf_name', SORT_STRING)
        ->mapWithKeys(fn (VrfModel $vrf) => [$vrf->vrf_name => [$vrf->context_manual, $vrf->vrf_manual]])->all();
}

/**
 * @return array<string, array{0: string|null, 1: bool}>
 */
function vrfContexts(): array
{
    return test()->device->vrfs()->get()->sortBy('vrf_name', SORT_STRING)
        ->mapWithKeys(fn (VrfModel $vrf) => [$vrf->vrf_name => [$vrf->context_name, $vrf->context_verified]])->all();
}

/**
 * @param  VrfSpec[]  $vrfs
 * @param  string[]  $answering  contexts that answer
 * @param  bool  $timeout  the device does not answer VRF discovery
 */
function runVrfModule(array $vrfs, array $answering = [], bool $poll = false, bool $timeout = false): TestVrfModule
{
    $os = Mockery::mock(OS::class);
    $os->shouldReceive('getDevice')->andReturn(test()->device);
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
                $port = test()->device->ports()->firstWhere('ifIndex', $ifIndex);
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
        $module->discover($os);
    }

    return $module;
}

/**
 * @param  array<string, mixed>  $fields
 * @return VrfSpec
 */
function vrfSpec(string $vrfOid, string $name, array $fields = []): array
{
    return array_merge(['vrf_oid' => $vrfOid, 'name' => $name, 'rd' => null, 'description' => '', 'context' => null, 'ifIndexes' => []], $fields);
}

/**
 * @phpstan-type VrfSpec array{vrf_oid: string, name: string, rd: string|null, description: string, context: string|null, ifIndexes: int[]}
 */
class TestVrfModule extends Vrf
{
    /** @var string[] */
    public array $probed = [];

    /**
     * @param  string[]  $answering
     */
    public function __construct(private readonly array $answering)
    {
    }

    protected function checkContext(string $context): bool
    {
        $this->probed[] = $context;

        return in_array($context, $this->answering, true);
    }

    public function checkDeviceContext(string $context): bool
    {
        return parent::checkContext($context);
    }
}
