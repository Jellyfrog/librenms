<?php

/**
 * BgpPeersTest.php
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

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Actions\Device\AutoDiscoverDevice;
use App\Facades\DeviceCache;
use App\Facades\LibrenmsConfig;
use App\Models\BgpPeer;
use App\Models\BgpPeerCbgp;
use App\Models\Device;
use App\Models\Eventlog;
use App\Models\Vrf;
use LibreNMS\Data\Source\Snmp\RawSnmpResponse;
use LibreNMS\Data\Source\Snmp\SnmpBackendInterface;
use LibreNMS\Exceptions\SnmpException;
use LibreNMS\Interfaces\Data\DataStorageInterface;
use LibreNMS\Interfaces\Discovery\BgpAddressFamilyDiscovery;
use LibreNMS\Modules\BgpPeers;
use LibreNMS\OS;
use PHPUnit\Framework\Attributes\DataProvider;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    LibrenmsConfig::set('autodiscovery.bgp', false);
    LibrenmsConfig::set('astext.65001', 'Peer AS');
    LibrenmsConfig::set('astext.65002', 'Other AS');
    LibrenmsConfig::set('alerts.bgp.whitelist', null);

    $this->device = Device::factory()->create(['os' => 'generic']);
});

test('repeated discovery keeps the same row', function () {
    $existing = $this->device->bgppeers()->create(peerAttributes('10.0.0.1'));
    $os = os([peer('10.0.0.1')]);

    $module = new BgpPeers;
    $module->discover($os);
    $module->discover($os);

    $peer = $this->device->bgppeers()->sole();
    expect($peer->bgpPeer_id)->toBe($existing->bgpPeer_id);
    expect($peer->astext)->toBe('Peer AS');
    expect($this->device->fresh()->bgpLocalAs)->toBe(65000);
    expect($module->dataExists($this->device))->toBeTrue();
});

test('discovery leaves session state to polling', function () {
    $this->device->bgppeers()->create(peerAttributes('10.0.0.1', ['bgpPeerState' => 'established', 'bgpPeerInUpdates' => 10]));
    $os = os([
        peer('10.0.0.1', ['bgpPeerRemoteAs' => 65002, 'bgpPeerState' => 'idle', 'bgpPeerInUpdates' => 20]),
        peer('10.0.0.2', ['bgpPeerState' => 'active']),
    ]);

    (new BgpPeers)->discover($os);

    $peers = $this->device->bgppeers()->orderBy('bgpPeerIdentifier')->get();
    expect($peers->pluck('bgpPeerIdentifier')->all())->toBe(['10.0.0.1', '10.0.0.2']);
    expect($peers[0]->bgpPeerRemoteAs)->toBe(65002, 'Discovery updates the peer AS');
    expect($peers[0]->bgpPeerState)->toBe('established', 'Existing session state is only updated by polling');
    expect($peers[0]->bgpPeerInUpdates)->toBe(10);
    expect($peers[1]->bgpPeerState)->toBe('active', 'New sessions start with their current state');
    expect($peers[1]->bgpPeerInUpdates)->toBe(0, 'Missing counters use defaults');
});

test('sessions are kept per context', function () {
    $vrf = contextVrf('vrf-b', 'B');
    $os = os([
        peer('10.0.0.1'),
        peer('10.0.0.1', ['bgpPeerRemoteAs' => 65002, 'context_name' => 'vrf-b', 'vrf_id' => $vrf->vrf_id]),
    ], [
        family('10.0.0.1', ['AcceptedPrefixes' => 5]),
        family('10.0.0.1', ['AcceptedPrefixes' => 7, 'context_name' => 'vrf-b']),
    ]);

    (new BgpPeers)->discover($os);

    $peers = $this->device->bgppeers()->orderBy('context_name')->get();
    expect($peers->map(fn (BgpPeer $peer) => [$peer->context_name, $peer->bgpPeerRemoteAs, $peer->vrf_id])->all())->toBe([['', 65001, null], ['vrf-b', 65002, $vrf->vrf_id]]);
    expect($this->device->bgpPeersCbgp()->orderBy('context_name')->pluck('AcceptedPrefixes')->all())->toBe([5, 7], 'Address families are kept per context');
});

test('skipped contexts keep their peers', function () {
    contextVrf('vrf-b', 'B', ['context_enabled' => false]);
    $this->device->bgppeers()->create(peerAttributes('10.0.0.1', ['context_name' => 'vrf-b']));
    $os = os([peer('10.0.0.1')]);

    (new BgpPeers)->discover($os);

    expect($this->device->bgppeers()->orderBy('context_name')->pluck('context_name')->all())->toBe(['', 'vrf-b']);

    $datastore = Mockery::mock(DataStorageInterface::class);
    $datastore->shouldReceive('put')->once()->withArgs(fn ($device, $measurement) => $measurement == 'bgp');

    (new BgpPeers)->poll($os, $datastore);
});

test('unanswered discovery keeps sessions', function () {
    $this->device->bgppeers()->create(peerAttributes('10.0.0.1'));
    $os = os(exception: new SnmpException('Timeout'));

    (new BgpPeers)->discover($os);

    expect($this->device->bgppeers()->pluck('bgpPeerIdentifier')->all())->toBe(['10.0.0.1']);
});

test('only new sessions with an address are auto discovered', function () {
    LibrenmsConfig::set('autodiscovery.bgp', true);
    $this->device->bgppeers()->create(peerAttributes('10.0.0.1'));
    $this->app->instance(AutoDiscoverDevice::class, Mockery::mock(AutoDiscoverDevice::class, function ($mock): void {
        $mock->shouldReceive('execute')->once()->withArgs(fn ($hostname) => in_array($hostname, ['10.0.0.2', gethostbyaddr('10.0.0.2')]));
    }));
    $os = os([
        peer('10.0.0.1'),
        peer('10.0.0.2'),
        peer('swp27', ['bgpPeerRemoteAddr' => '', 'bgpPeerIface' => 27]),
    ]);

    (new BgpPeers)->discover($os);

    expect($this->device->bgppeers()->orderBy('bgpPeerIdentifier')->pluck('bgpPeerIdentifier')->all())->toBe(['10.0.0.1', '10.0.0.2', 'swp27']);
});

test('address families of unknown peers are ignored', function () {
    $os = os([peer('10.0.0.1')], [
        family('10.0.0.1', ['AcceptedPrefixes' => 5]),
        family('10.0.0.1', ['AcceptedPrefixes' => 9]),
        family('10.0.0.7', ['AcceptedPrefixes' => 5]),
        family('10.0.0.1', ['AcceptedPrefixes' => 5, 'context_name' => 'vrf-b']),
    ]);

    (new BgpPeers)->discover($os);

    $family = $this->device->bgpPeersCbgp()->sole();
    expect([$family->bgpPeerIdentifier, $family->afi, $family->safi, $family->AcceptedPrefixes, $family->AcceptedPrefixes_delta])->toBe(['10.0.0.1', 'ipv4', 'unicast', 5, 0], 'The first family reported wins');
});

test('empty address family walk keeps existing families', function () {
    $peers = [peer('10.0.0.1')];
    (new BgpPeers)->discover(os($peers, [family('10.0.0.1', ['AcceptedPrefixes' => 5])]));

    (new BgpPeers)->discover(os($peers));
    expect($this->device->bgpPeersCbgp()->sole()->AcceptedPrefixes)->toBe(5);

    (new BgpPeers)->discover(os());
    expect($this->device->bgpPeersCbgp()->exists())->toBeFalse('Families are removed with their sessions');
});

test('families are removed when the os has no address families', function () {
    $this->device->bgpPeersCbgp()->save((new BgpPeerCbgp)->forceFill(family('10.0.0.1') + BgpPeerCbgp::emptyCounters()));

    (new BgpPeers)->discover(os([peer('10.0.0.1')], null));

    expect($this->device->bgpPeersCbgp()->exists())->toBeFalse();
});

test('polling updates sessions and logs state changes', function () {
    $this->device->bgppeers()->create(peerAttributes('10.0.0.1', ['bgpPeerState' => 'established', 'bgpPeerFsmEstablishedTime' => 500]));
    $this->device->bgppeers()->create(peerAttributes('10.0.0.2', ['bgpPeerState' => 'established', 'bgpPeerFsmEstablishedTime' => 500]));
    $this->device->bgppeers()->create(peerAttributes('10.0.0.3', ['bgpPeerState' => 'established', 'bgpPeerFsmEstablishedTime' => 500]));
    $this->device->bgppeers()->create(peerAttributes('10.0.0.4'));
    $os = os([
        peer('10.0.0.1', ['bgpPeerState' => 'established', 'bgpPeerFsmEstablishedTime' => 800, 'bgpPeerInUpdates' => 42]),
        peer('10.0.0.2', ['bgpPeerState' => 'established', 'bgpPeerFsmEstablishedTime' => 5]),
        peer('10.0.0.3', ['bgpPeerState' => 'idle', 'bgpPeerFsmEstablishedTime' => 5, 'bgpPeerLastErrorCode' => 6, 'bgpPeerLastErrorSubCode' => 2]),
        peer('10.0.0.5'),
    ]);

    $datastore = Mockery::mock(DataStorageInterface::class);
    $datastore->shouldReceive('put')->times(3)->withArgs(fn ($device, $measurement) => $measurement == 'bgp');

    (new BgpPeers)->poll($os, $datastore);

    $peers = $this->device->bgppeers()->orderBy('bgpPeerIdentifier')->get();
    expect($peers->pluck('bgpPeerIdentifier')->all())->toBe(['10.0.0.1', '10.0.0.2', '10.0.0.3', '10.0.0.4'], 'Polling does not add or remove sessions');
    expect($peers[0]->bgpPeerInUpdates)->toBe(42);
    expect($peers[2]->bgpPeerState)->toBe('idle');

    $events = Eventlog::where('device_id', $this->device->device_id)->where('type', 'bgpPeer')->orderBy('reference')->pluck('message', 'reference');
    expect($events)->toHaveCount(2, 'An established session that kept counting is not logged');
    expect($events['10.0.0.2'])->toStartWith('BGP Session Flap: 10.0.0.2');
    expect($events['10.0.0.3'])->toStartWith('BGP Session Down: 10.0.0.3');
    $this->assertStringContainsString('Administrative Shutdown', $events['10.0.0.3']);
});

/**
 * @return array<string, array{?string, ?string, ?int, ?int}>
 */
dataset('timosMessageCounters', function () {
    return [
        'normal' => ['123', '456', 123, 456],
        'Counter32 limits' => ['4294967295', '0', 4294967295, 0],
        'missing receive' => [null, '456', null, 456],
        'missing send' => ['123', null, 123, null],
        'unsupported counters' => [null, null, null, null],
        'malformed counters' => ['123 abc', 'invalid', null, null],
    ];
});

test('timos total messages', function (?string $received, ?string $sent, ?int $expectedReceived, ?int $expectedSent) {
    $this->device->update(['os' => 'timos']);
    $index = '1.1.4.192.0.2.1';
    $values = [
        'tBgpPeerNgPeerAS4Byte' => '65001',
        'tBgpPeerNgConnState' => 'established',
        'tBgpPeerNgShutdown' => '2',
        'tBgpPeerFsmEstablishedTime' => '100',
        'tBgpPeerNgOperInUpdates' => '7',
        'tBgpPeerNgOperOutUpdates' => '9',
        'tBgpPeerNgOperInTotalMessages' => $received,
        'tBgpPeerNgOperOutTotalMessages' => $sent,
        'tBgpPeerNgOperMsgOctetsRcvd' => '18446744071926153491',
        'tBgpPeerNgOperMsgOctetsSent' => '18446744073709551615',
    ];
    $walked = [];
    $backend = Mockery::mock(SnmpBackendInterface::class);
    $backend->shouldReceive('walk')
        ->andReturnUsing(function (string $target, string $oid) use ($values, $index, &$walked) {
            $walked[] = $oid;
            $column = explode('::', $oid)[1];

            return new RawSnmpResponse(isset($values[$column]) ? "$column.$index = {$values[$column]}" : '');
        });
    $backend->shouldReceive('get')
        ->andReturn(new RawSnmpResponse('BGP4-MIB::bgpLocalAs.0 = 65000'));
    $this->app->instance(SnmpBackendInterface::class, $backend);

    try {
        DeviceCache::setPrimary($this->device->device_id);
        $deviceArray = $this->device->toArray();
        $os = OS::make($deviceArray);
        $module = new BgpPeers;
        $module->discover($os);

        $peer = $this->device->bgppeers()->sole();
        expect([$peer->bgpPeerInTotalMessages, $peer->bgpPeerOutTotalMessages])->toBe([$expectedReceived ?? 0, $expectedSent ?? 0]);
        $peer->update(['bgpPeerInTotalMessages' => 11, 'bgpPeerOutTotalMessages' => 22]);

        $datastore = Mockery::mock(DataStorageInterface::class);
        $datastore->shouldReceive('put')->once()->withArgs(function ($device, $measurement, $tags, $fields) use ($expectedReceived, $expectedSent) {
            expect($measurement)->toBe('bgp');
            expect($fields['bgpPeerInTotal'])->toBe($expectedReceived ?? 11);
            expect($fields['bgpPeerOutTotal'])->toBe($expectedSent ?? 22);
            expect([$fields['bgpPeerInUpdates'], $fields['bgpPeerOutUpdates'], $fields['bgpPeerEstablished']])->toBe([7, 9, 100]);

            return true;
        });
        $module->poll($os, $datastore);

        $peer->refresh();
        expect([$peer->bgpPeerInTotalMessages, $peer->bgpPeerOutTotalMessages])->toBe([$expectedReceived ?? 11, $expectedSent ?? 22]);
        expect($walked)->toContain('TIMETRA-BGP-MIB::tBgpPeerNgOperInTotalMessages');
        expect($walked)->toContain('TIMETRA-BGP-MIB::tBgpPeerNgOperOutTotalMessages');
        expect($walked)->not->toContain('TIMETRA-BGP-MIB::tBgpPeerNgOperMsgOctetsRcvd');
        expect($walked)->not->toContain('TIMETRA-BGP-MIB::tBgpPeerNgOperMsgOctetsSent');
    } finally {
        DeviceCache::flush();
    }
})->with('timosMessageCounters');

test('polling records prefix changes', function () {
    $peers = [peer('10.0.0.1')];
    (new BgpPeers)->discover(os($peers, [family('10.0.0.1', ['afi' => 'ipv6', 'safi' => 'vpn', 'AcceptedPrefixes' => 10, 'AdvertisedPrefixes' => 3])]));

    $datastore = Mockery::mock(DataStorageInterface::class);
    $datastore->shouldReceive('put')->withArgs(fn ($device, $measurement) => $measurement == 'bgp');
    $datastore->shouldReceive('put')->once()->withArgs(fn ($device, $measurement, $tags, $fields) => $measurement == 'cbgp' && $tags['afi'] == 'ipv6' && $tags['safi'] == 'vpn' && $fields['AcceptedPrefixes'] == 15);

    (new BgpPeers)->poll(os($peers, [family('10.0.0.1', ['afi' => 'ipv6', 'safi' => 'vpn', 'AcceptedPrefixes' => 15, 'AdvertisedPrefixes' => 3])]), $datastore);

    $family = $this->device->bgpPeersCbgp()->sole();
    expect([$family->AcceptedPrefixes, $family->AcceptedPrefixes_prev, $family->AcceptedPrefixes_delta])->toBe([15, 10, 5]);
    expect([$family->AdvertisedPrefixes, $family->AdvertisedPrefixes_prev, $family->AdvertisedPrefixes_delta])->toBe([3, 3, 0]);
});

test('removed peers are deleted', function () {
    $this->device->bgppeers()->create(peerAttributes('10.0.0.1'));
    $this->device->bgppeers()->create(peerAttributes('10.0.0.1', ['context_name' => 'gone']));
    $os = os([peer('10.0.0.2')], [family('10.0.0.2')]);

    $module = new BgpPeers;
    $module->discover($os);

    expect($this->device->bgppeers()->pluck('bgpPeerIdentifier')->all())->toBe(['10.0.0.2']);

    expect($module->cleanup($this->device))->toBe(2);
    expect($module->dataExists($this->device))->toBeFalse();
});

/**
 * An OS that reports the given sessions, new models on each call like a real walk
 *
 * @param  array<int, array<string, mixed>>  $peers  BgpPeer attributes
 * @param  array<int, array<string, mixed>>|null  $families  BgpPeerCbgp attributes, null for an OS without address families
 */
function os(array $peers = [], ?array $families = [], ?SnmpException $exception = null): OS
{
    $os = $families === null ? Mockery::mock(OS::class) : Mockery::mock(OS::class, BgpAddressFamilyDiscovery::class);
    $os->shouldReceive('getDevice')->andReturn($this->device);
    $os->shouldReceive('getDeviceArray')->andReturn($this->device->toArray());
    $os->shouldReceive('discoverBgpLocalAs')->andReturn(65000);
    $os->shouldReceive('discoverBgpPeers')->andReturnUsing(fn () => $exception ? throw $exception : collect($peers)->map(fn (array $peer) => new BgpPeer($peer)));
    if ($families !== null) {
        $os->shouldReceive('discoverBgpAddressFamilies')->andReturnUsing(fn () => collect($families)->map(fn (array $family) => new BgpPeerCbgp($family)));
    }

    /** @var OS $os */
    return $os;
}

/**
 * @param  array<string, mixed>  $attributes
 * @return array<string, mixed>
 */
function peer(string $identifier, array $attributes = []): array
{
    return array_merge(['bgpPeerIdentifier' => $identifier, 'bgpPeerRemoteAddr' => $identifier, 'bgpPeerRemoteAs' => 65001, 'context_name' => ''], $attributes);
}

/**
 * @param  array<string, mixed>  $attributes
 * @return array<string, mixed>
 */
function family(string $identifier, array $attributes = []): array
{
    return array_merge(['bgpPeerIdentifier' => $identifier, 'afi' => 'ipv4', 'safi' => 'unicast', 'context_name' => ''], $attributes);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function contextVrf(string $context, string $name, array $attributes = []): Vrf
{
    return Vrf::factory()->for($this->device)->create(array_merge([
        'vrf_name' => $name,
        'context_name' => $context,
        'context_verified' => true,
    ], $attributes));
}

/**
 * @param  array<string, mixed>  $attributes
 * @return array<string, mixed>
 */
function peerAttributes(string $ip, array $attributes = []): array
{
    return array_merge([
        'context_name' => '',
        'bgpPeerIdentifier' => $ip,
        'bgpPeerRemoteAs' => 65001,
        'bgpPeerState' => 'established',
        'bgpPeerAdminStatus' => 'start',
        'bgpLocalAddr' => '0.0.0.0',
        'bgpPeerRemoteAddr' => $ip,
        'bgpPeerInUpdates' => 0,
        'bgpPeerOutUpdates' => 0,
        'bgpPeerInTotalMessages' => 0,
        'bgpPeerOutTotalMessages' => 0,
        'bgpPeerFsmEstablishedTime' => 0,
        'bgpPeerInUpdateElapsedTime' => 0,
        'astext' => 'AS65001',
    ], $attributes);
}