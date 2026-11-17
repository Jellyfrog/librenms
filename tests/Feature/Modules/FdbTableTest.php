<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\Port;
use App\Models\PortsFdb;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use LibreNMS\Data\Source\Snmp\SnmpQueryBuilder;
use LibreNMS\Data\Source\Snmp\SnmpResponse;
use LibreNMS\Modules\FdbTable;
use LibreNMS\OS;
use PHPUnit\Framework\Attributes\DataProvider;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

private const string MAC_A = '00112233aa01';
private const string MAC_B = '00112233aa02';

beforeEach(function () {
    $this->device = Device::factory()->create(['os' => 'generic']);
});

test('new entries are created with the vlan number', function () {
    $port = port(10);

    discover(fn () => [fdb(self::MAC_A, $port->port_id, 100)]);

    $entry = $this->device->portsFdb()->sole();
    expect($entry->mac_address)->toBe(self::MAC_A);
    expect($entry->port_id)->toBe($port->port_id);
    expect($entry->vlan)->toBe(100);
});

test('edgeswitch normalizes reported vlans', function (int|string|null $value, ?int $expected) {
    port(10);
    $binding = ['EdgeSwitch-SWITCHING-MIB::agentDynamicDsBindingIfIndex[0:11:22:33:aa:1]' => '10'];
    if ($value !== null) {
        $binding['EdgeSwitch-SWITCHING-MIB::agentDynamicDsBindingVlanId[0:11:22:33:aa:1]'] = (string) $value;
    }

    $fdb = osFdb('edgeswitch', ['|EdgeSwitch-SWITCHING-MIB::agentDynamicDsBindingTable' => $binding]);

    expect($fdb)->toBe([[self::MAC_A, portId(10), $expected]]);
})->with('vlanValues');

/**
 * @return array<string, array{int|string|null, int|null}>
 */
dataset('vlanValues', function () {
    return [
        'unknown' => [null, null],
        'zero grouping key' => [0, null],
        'string zero grouping key' => ['0', null],
        'known number' => [100, 100],
        'known string' => ['100', 100],
    ];
});

test('unknown vlan is stored and rediscovered separately from known vlans', function () {
    $port = port(10);
    $unknown = new PortsFdb(['mac_address' => self::MAC_A, 'port_id' => $port->port_id]);
    expect($unknown->vlan)->toBeNull();

    discover(fn () => [$unknown, fdb(self::MAC_A, $port->port_id, 100)]);
    $entry = $this->device->portsFdb()->whereNull('vlan')->sole();
    expect($entry->toArray()['vlan'])->toBeNull();

    discover(fn () => [fdb(self::MAC_A, $port->port_id)]);

    expect($this->device->portsFdb()->count())->toBe(2);
    expect($this->device->portsFdb()->whereNull('vlan')->sole()->ports_fdb_id)->toBe($entry->ports_fdb_id);
});

test('entries no longer learned are kept with their last seen time', function () {
    $port = port(10);
    $this->travelTo(Carbon::parse('2026-01-01 00:00:00'));
    discover(fn () => [fdb(self::MAC_A, $port->port_id), fdb(self::MAC_B, $port->port_id)]);

    $this->travelTo(Carbon::parse('2026-01-02 00:00:00'));
    discover(fn () => [fdb(self::MAC_A, $port->port_id)]);

    $entries = $this->device->portsFdb()->get()->keyBy('mac_address');
    expect($entries)->toHaveCount(2, 'ports_fdb_purge is responsible for removing old entries');
    expect($entries[self::MAC_A]->updated_at->toDateTimeString())->toBe('2026-01-02 00:00:00');
    expect($entries[self::MAC_A]->created_at->toDateTimeString())->toBe('2026-01-01 00:00:00');
    expect($entries[self::MAC_B]->updated_at->toDateTimeString())->toBe('2026-01-01 00:00:00');
});

test('nothing changes when the device returns no entries', function () {
    $port = port(10);
    discover(fn () => [fdb(self::MAC_A, $port->port_id)]);

    discover(fn () => []);

    expect($this->device->portsFdb()->count())->toBe(1);
});

test('moved mac updates the port in place', function () {
    $from = port(10);
    $to = port(11);
    discover(fn () => [fdb(self::MAC_A, $from->port_id)]);
    $id = $this->device->portsFdb()->sole()->ports_fdb_id;

    discover(fn () => [fdb(self::MAC_A, $to->port_id)]);

    $entry = $this->device->portsFdb()->sole();
    expect($entry->ports_fdb_id)->toBe($id);
    expect($entry->port_id)->toBe($to->port_id);
});

test('unknown port does not overwrite known port', function () {
    $port = port(10);
    discover(fn () => [fdb(self::MAC_A, $port->port_id)]);

    discover(fn () => [fdb(self::MAC_A, 0)]);

    expect($this->device->portsFdb()->sole()->port_id)->toBe($port->port_id);
});

test('unknown port is stored for new entries', function () {
    discover(fn () => [fdb(self::MAC_A, 0)]);

    expect($this->device->portsFdb()->sole()->port_id)->toBe(0);
});

test('same mac in different vlans are separate entries', function () {
    $port = port(10);

    discover(fn () => [
        fdb(self::MAC_A, $port->port_id, 100),
        fdb(self::MAC_A, $port->port_id, 200),
    ]);

    expect($this->device->portsFdb()->count())->toBe(2);
});

test('invalid macs are dropped', function () {
    $port = port(10);

    discover(fn () => [fdb('', $port->port_id), fdb('0011', $port->port_id), fdb(self::MAC_A, $port->port_id)]);

    expect($this->device->portsFdb()->sole()->mac_address)->toBe(self::MAC_A);
});

test('duplicate rows are collapsed', function () {
    $port = port(10);
    foreach ([1, 2] as $ignored) {
        PortsFdb::factory()->create(['device_id' => $this->device->device_id, 'port_id' => $port->port_id, 'mac_address' => self::MAC_A, 'vlan' => null]);
    }

    discover(fn () => [fdb(self::MAC_A, $port->port_id)]);

    expect($this->device->portsFdb()->count())->toBe(1);
});

test('other devices are not touched', function () {
    $other = Device::factory()->create();
    PortsFdb::factory()->create(['device_id' => $other->device_id, 'port_id' => 1, 'mac_address' => self::MAC_A, 'vlan' => null]);
    $port = port(10);

    discover(fn () => [fdb(self::MAC_A, $port->port_id)]);

    expect($other->portsFdb()->count())->toBe(1);
    expect($this->device->portsFdb()->count())->toBe(1);
});

test('numeric looking macs are matched independently on rediscovery', function () {
    $macs = ['000000000e01', '000000000e02'];
    discover(fn () => array_map(fn ($mac) => fdb($mac, 0), $macs));
    $ids = $this->device->portsFdb()->orderBy('ports_fdb_id')->pluck('ports_fdb_id')->all();

    discover(fn () => array_map(fn ($mac) => fdb($mac, 0), $macs));

    expect($ids)->toHaveCount(2);
    expect($this->device->portsFdb()->orderBy('ports_fdb_id')->pluck('ports_fdb_id')->all())->toBe($ids);
});

test('persistence does not dispatch eloquent events', function () {
    $events = array_map(fn ($event) => "eloquent.$event: " . PortsFdb::class, ['saving', 'saved', 'creating', 'created', 'updating', 'updated', 'deleting', 'deleted']);
    Event::fake($events);

    discover(fn () => [fdb(self::MAC_A, 0)]);
    discover(fn () => [fdb(self::MAC_A, 1)]);

    foreach ($events as $event) {
        Event::assertNotDispatched($event);
    }
    expect($this->device->portsFdb()->sole()->port_id)->toBe(1);
});

test('dump matches test data format', function () {
    $port = port(10);
    discover(fn () => [fdb(self::MAC_B, $port->port_id, 100), fdb(self::MAC_A, $port->port_id, 100), fdb('00112233aa03', 0)]);

    $module = new FdbTable;

    expect($module->dump($this->device, 'poller'))->toBeNull();
    expect($module->dump($this->device, 'discovery')['ports_fdb']->toArray())->toBe([
        ['mac_address' => '00112233aa03', 'vlan' => null, 'ifIndex' => null],
        ['mac_address' => self::MAC_A, 'vlan' => 100, 'ifIndex' => 10],
        ['mac_address' => self::MAC_B, 'vlan' => 100, 'ifIndex' => 10],
    ]);
});

test('data exists and cleanup', function () {
    $module = new FdbTable;
    expect($module->dataExists($this->device))->toBeFalse();

    $port = port(10);
    discover(fn () => [fdb(self::MAC_A, $port->port_id)]);
    expect($module->dataExists($this->device))->toBeTrue();

    expect($module->cleanup($this->device))->toBe(1);
    expect($module->dataExists($this->device))->toBeFalse();
});

test('qbridge maps fdb ids to vlans', function () {
    port(10);
    port(11);

    $fdb = osFdb('generic', [
        '|Q-BRIDGE-MIB::dot1qTpFdbPort' => [
            'Q-BRIDGE-MIB::dot1qTpFdbPort[7][0:11:22:33:aa:1]' => '1', // fdb id 7 is VLAN 100
            'Q-BRIDGE-MIB::dot1qTpFdbPort[100][0:11:22:33:aa:2]' => '2', // no fdb id, the index is the VLAN
            'Q-BRIDGE-MIB::dot1qTpFdbPort[100][0:11:22:33:aa:3]' => '0', // the switch itself
        ],
        '|Q-BRIDGE-MIB::dot1qVlanFdbId' => [
            'Q-BRIDGE-MIB::dot1qVlanFdbId[0][100]' => '7',
            'Q-BRIDGE-MIB::dot1qVlanFdbId[1][100]' => '7',
        ],
        '|BRIDGE-MIB::dot1dBasePortIfIndex' => ['BRIDGE-MIB::dot1dBasePortIfIndex[1]' => '10', 'BRIDGE-MIB::dot1dBasePortIfIndex[2]' => '11'],
    ]);

    expect($fdb)->toBe([
        [self::MAC_A, portId(10), 100],
        [self::MAC_B, portId(11), 100],
    ]);
});

test('qbridge reports shared fdb ids as unknown vlan', function () {
    port(10);

    $fdb = osFdb('generic', [
        '|Q-BRIDGE-MIB::dot1qTpFdbPort' => [
            'Q-BRIDGE-MIB::dot1qTpFdbPort[7][0:11:22:33:aa:1]' => '1',
        ],
        '|Q-BRIDGE-MIB::dot1qVlanFdbId' => [
            'Q-BRIDGE-MIB::dot1qVlanFdbId[0][100]' => '7',
            'Q-BRIDGE-MIB::dot1qVlanFdbId[0][200]' => '7',
            'Q-BRIDGE-MIB::dot1qVlanFdbId[0][300]' => '7',
            'Q-BRIDGE-MIB::dot1qVlanFdbId[1][100]' => '7',
        ],
        '|BRIDGE-MIB::dot1dBasePortIfIndex' => ['BRIDGE-MIB::dot1dBasePortIfIndex[1]' => '10'],
    ]);

    expect($fdb)->toBe([[self::MAC_A, portId(10), null]]);
});

test('bridge mib is used without qbridge and has no vlan', function () {
    port(10);

    $fdb = osFdb('generic', [
        '|BRIDGE-MIB::dot1dTpFdbPort' => ['BRIDGE-MIB::dot1dTpFdbPort[0:11:22:33:aa:1]' => '1'],
        '|BRIDGE-MIB::dot1dBasePortIfIndex' => ['BRIDGE-MIB::dot1dBasePortIfIndex[1]' => '10'],
    ]);

    expect($fdb)->toBe([[self::MAC_A, portId(10), null]]);
});

test('bridge port is assumed to be if index when unmapped and unknown ports are kept', function () {
    port(10);

    $fdb = osFdb('generic', [
        '|BRIDGE-MIB::dot1dTpFdbPort' => [
            'BRIDGE-MIB::dot1dTpFdbPort[0:11:22:33:aa:1]' => '10',
            'BRIDGE-MIB::dot1dTpFdbPort[0:11:22:33:aa:2]' => '99',
        ],
    ]);

    expect($fdb)->toBe([[self::MAC_A, portId(10), null], [self::MAC_B, 0, null]]);
});

test('length prefixed mac indexes are fixed', function () {
    port(10);

    $fdb = osFdb('generic', [
        '|BRIDGE-MIB::dot1dTpFdbPort' => ['BRIDGE-MIB::dot1dTpFdbPort[6:0:a:f7:ec:d1].97' => '1'],
        '|BRIDGE-MIB::dot1dBasePortIfIndex' => ['BRIDGE-MIB::dot1dBasePortIfIndex[1]' => '10'],
    ]);

    expect($fdb)->toBe([['000af7ecd161', portId(10), null]]);
});

test('cisco walks each operational vlan context', function () {
    LibrenmsConfig::set('os.ios.ignore_vlans', [300]);
    port(10);

    $fdb = osFdb('ios', [
        '|CISCO-VTP-MIB::vtpVlanState' => [
            'CISCO-VTP-MIB::vtpVlanState[1][100]' => '1',
            'CISCO-VTP-MIB::vtpVlanState[1][200]' => '2', // suspended
            'CISCO-VTP-MIB::vtpVlanState[1][300]' => '1', // ignored
            'CISCO-VTP-MIB::vtpVlanState[1][1002]' => '1', // reserved
        ],
        '100|BRIDGE-MIB::dot1dTpFdbPort' => ['BRIDGE-MIB::dot1dTpFdbPort[0:11:22:33:aa:1]' => '1', 'BRIDGE-MIB::dot1dTpFdbPort[0:11:22:33:aa:2]' => '2'],
        '100|BRIDGE-MIB::dot1dBasePortIfIndex' => ['BRIDGE-MIB::dot1dBasePortIfIndex[1]' => '10'],
        '200|BRIDGE-MIB::dot1dTpFdbPort' => ['BRIDGE-MIB::dot1dTpFdbPort[0:11:22:33:bb:1]' => '1'],
        '300|BRIDGE-MIB::dot1dTpFdbPort' => ['BRIDGE-MIB::dot1dTpFdbPort[0:11:22:33:cc:1]' => '1'],
        '1002|BRIDGE-MIB::dot1dTpFdbPort' => ['BRIDGE-MIB::dot1dTpFdbPort[0:11:22:33:dd:1]' => '1'],
    ]);

    // MAC_B has no known bridge port in this context
    expect($fdb)->toBe([[self::MAC_A, portId(10), 100]]);
});

test('cisco does not create vlans', function () {
    port(10);

    $fdb = osFdb('ios', [
        '|CISCO-VTP-MIB::vtpVlanState' => ['CISCO-VTP-MIB::vtpVlanState[1][100]' => '1'],
        '100|BRIDGE-MIB::dot1dTpFdbPort' => ['BRIDGE-MIB::dot1dTpFdbPort[0:11:22:33:aa:1]' => '1'],
        '100|BRIDGE-MIB::dot1dBasePortIfIndex' => ['BRIDGE-MIB::dot1dBasePortIfIndex[1]' => '10'],
    ]);

    expect($fdb)->toBe([[self::MAC_A, portId(10), 100]]);
    expect($this->device->vlans()->count())->toBe(0);
});

test('vendor mibs override standard mibs', function (string $os, array $vendorData) {
    port(10);

    $standard = [
        '|Q-BRIDGE-MIB::dot1qTpFdbPort' => ['Q-BRIDGE-MIB::dot1qTpFdbPort[100][0:11:22:33:ff:ff]' => '10'],
    ];

    expect(osFdb($os, $vendorData + $standard))->toBe([[self::MAC_A, portId(10), 100]]);
})->with('vendorMibs');

test('standard mibs are used when vendor mibs have no data', function (string $os, array $vendorData) {
    port(10);

    expect(osFdb($os, [
        '|Q-BRIDGE-MIB::dot1qTpFdbPort' => ['Q-BRIDGE-MIB::dot1qTpFdbPort[100][0:11:22:33:ff:ff]' => '10'],
    ]))->toBe([['00112233ffff', portId(10), 100]]);
})->with('vendorMibs');

/**
 * @return array<string, array{string, array<string, array<string, string>>}>
 */
dataset('vendorMibs', function () {
    return [
        'aos6' => ['aos6', [
            '|ALCATEL-IND1-MAC-ADDRESS-MIB::slMacAddressDisposition' => ['ALCATEL-IND1-MAC-ADDRESS-MIB::slMacAddressDisposition[10][100][0:11:22:33:aa:1]' => '1'],
        ]],
        'aos7' => ['aos7', [
            '|ALCATEL-IND1-MAC-ADDRESS-MIB::slMacAddressGblManagement' => ['ALCATEL-IND1-MAC-ADDRESS-MIB::slMacAddressGblManagement[1][1][1][100][0][0:11:22:33:aa:1]' => '1'],
            '|BRIDGE-MIB::dot1dBasePortIfIndex' => ['BRIDGE-MIB::dot1dBasePortIfIndex[1]' => '10'],
        ]],
        'timos' => ['timos', [
            '|TIMETRA-SERV-MIB::tlsFdbLocale' => ['TIMETRA-SERV-MIB::tlsFdbLocale[5][0:11:22:33:aa:1]' => '1'],
            '|TIMETRA-SERV-MIB::tlsFdbPortId' => ['TIMETRA-SERV-MIB::tlsFdbPortId[5][0:11:22:33:aa:1]' => '10'],
            '|TIMETRA-SERV-MIB::tlsFdbEncapValue' => ['TIMETRA-SERV-MIB::tlsFdbEncapValue[5][0:11:22:33:aa:1]' => '100'],
        ]],
        'vrp' => ['vrp', [
            '|HUAWEI-L2MAM-MIB::hwDynFdbPort' => ['HUAWEI-L2MAM-MIB::hwDynFdbPort[0:11:22:33:aa:1][100]["0"]' => '10'],
        ]],
        'cisco' => ['ios', [
            '|CISCO-VTP-MIB::vtpVlanState' => ['CISCO-VTP-MIB::vtpVlanState[1][100]' => '1'],
            '100|BRIDGE-MIB::dot1dTpFdbPort' => ['BRIDGE-MIB::dot1dTpFdbPort[0:11:22:33:aa:1]' => '1'],
            '100|BRIDGE-MIB::dot1dBasePortIfIndex' => ['BRIDGE-MIB::dot1dBasePortIfIndex[1]' => '10'],
        ]],
    ];
});

test('edgeswitch bindings add to the standard fdb', function () {
    port(10);
    port(11);

    $fdb = osFdb('edgeswitch', [
        '|EdgeSwitch-SWITCHING-MIB::agentDynamicDsBindingTable' => [
            'EdgeSwitch-SWITCHING-MIB::agentDynamicDsBindingIfIndex[0:11:22:33:aa:1]' => '10',
            'EdgeSwitch-SWITCHING-MIB::agentDynamicDsBindingVlanId[0:11:22:33:aa:1]' => '100',
            'EdgeSwitch-SWITCHING-MIB::agentDynamicDsBindingIfIndex[0:11:22:33:aa:2]' => '10',
            'EdgeSwitch-SWITCHING-MIB::agentDynamicDsBindingVlanId[0:11:22:33:aa:2]' => '100',
        ],
        '|Q-BRIDGE-MIB::dot1qTpFdbPort' => [
            'Q-BRIDGE-MIB::dot1qTpFdbPort[100][0:11:22:33:aa:2]' => '11',
            'Q-BRIDGE-MIB::dot1qTpFdbPort[100][0:11:22:33:ff:ff]' => '11',
        ],
    ]);

    // the order matters, the module keeps the last entry for a key so the standard FDB wins
    expect($fdb)->toBe([
        [self::MAC_A, portId(10), 100],
        [self::MAC_B, portId(10), 100],
        [self::MAC_B, portId(11), 100],
        ['00112233ffff', portId(11), 100],
    ]);
});

test('edgeswitch uses the standard fdb without bindings', function () {
    port(10);

    expect(osFdb('edgeswitch', [
        '|Q-BRIDGE-MIB::dot1qTpFdbPort' => ['Q-BRIDGE-MIB::dot1qTpFdbPort[100][0:11:22:33:aa:1]' => '10'],
    ]))->toBe([[self::MAC_A, portId(10), 100]]);
});

test('vrp sticky table does not remove dynamic entries', function () {
    port(10);

    $fdb = osFdb('vrp', [
        '|HUAWEI-L2MAM-MIB::hwDynFdbPort' => ['HUAWEI-L2MAM-MIB::hwDynFdbPort[0:11:22:33:aa:1][100]["0"]' => '10'],
        '|HUAWEI-L2MAM-MIB::hwCfgMacAddrQueryIfIndex' => ['HUAWEI-L2MAM-MIB::hwCfgMacAddrQueryIfIndex[100][0][0][0][0:11:22:33:aa:1][other][0][0][0][0][0]' => '10'],
    ]);

    expect($fdb)->toBe([[self::MAC_A, portId(10), 100]]);
});

test('jetstream does not treat bridge ports as if indexes', function () {
    port(1);

    // Vlan-interface1, dot1dBasePortIfIndex maps bridge port 1 to it on real devices
    $fdb = osFdb('jetstream', [
        '|Q-BRIDGE-MIB::dot1qTpFdbPort' => ['Q-BRIDGE-MIB::dot1qTpFdbPort[100][0:11:22:33:aa:1]' => '1'],
        '|BRIDGE-MIB::dot1dBasePortIfIndex' => ['BRIDGE-MIB::dot1dBasePortIfIndex[1]' => '1'],
    ]);

    expect($fdb)->toBe([[self::MAC_A, 0, 100]]);
});

/**
 * @return array<string, array{string}>
 */
dataset('rowIndexedOs', function () {
    return ['netonix' => ['netonix'], 'fortiswitch' => ['fortiswitch']];
});

test('row indexed os falls back to standard mibs', function (string $os) {
    port(10);

    $fdb = osFdb($os, [
        '|Q-BRIDGE-MIB::dot1qTpFdbPort' => ['Q-BRIDGE-MIB::dot1qTpFdbPort[100][0:11:22:33:aa:1]' => '1'],
        '|BRIDGE-MIB::dot1dBasePortIfIndex' => ['BRIDGE-MIB::dot1dBasePortIfIndex[1]' => '10'],
    ]);

    expect($fdb)->toBe([[self::MAC_A, portId(10), 100]]);
})->with('rowIndexedOs');

test('aruba does not walk acontext without avlan', function () {
    port(10);
    port(11);

    $fdb = osFdb('arubaos', [
        '|BRIDGE-MIB::dot1dTpFdbPort' => ['BRIDGE-MIB::dot1dTpFdbPort[0:11:22:33:aa:1]' => '1'],
        '|BRIDGE-MIB::dot1dBasePortIfIndex' => ['BRIDGE-MIB::dot1dBasePortIfIndex[1]' => '10'],
        '0|BRIDGE-MIB::dot1dBasePortIfIndex' => ['BRIDGE-MIB::dot1dBasePortIfIndex[1]' => '11'],
    ]);

    expect($fdb)->toBe([[self::MAC_A, portId(10), null]]);
});

test('aos falls back to aggregate and lag ports', function () {
    port(40000002);

    $fdb = osFdb('aos7', [
        '|ALCATEL-IND1-MAC-ADDRESS-MIB::slMacAddressGblManagement' => ['ALCATEL-IND1-MAC-ADDRESS-MIB::slMacAddressGblManagement[1][1][4099][0][0][0:11:22:33:aa:1]' => '1'],
    ]);

    expect($fdb)->toBe([[self::MAC_A, portId(40000002), null]]);
});

test('vrp reads sticky macs', function () {
    port(10);

    $fdb = osFdb('vrp', [
        '|HUAWEI-L2MAM-MIB::hwCfgMacAddrQueryIfIndex' => ['HUAWEI-L2MAM-MIB::hwCfgMacAddrQueryIfIndex[100][0][0][0][0:11:22:33:aa:1][showall][0][0][0][0][0]' => '10'],
    ]);

    expect($fdb)->toBe([[self::MAC_A, portId(10), 100]]);
});

test('vrp normalizes unknown vlans for dynamic and sticky macs', function () {
    port(10);

    $fdb = osFdb('vrp', [
        '|HUAWEI-L2MAM-MIB::hwDynFdbPort' => ['HUAWEI-L2MAM-MIB::hwDynFdbPort[0:11:22:33:aa:1][0]["0"]' => '10'],
        '|HUAWEI-L2MAM-MIB::hwCfgMacAddrQueryIfIndex' => ['HUAWEI-L2MAM-MIB::hwCfgMacAddrQueryIfIndex[0][0][0][0][0:11:22:33:aa:2][showall][0][0][0][0][0]' => '10'],
    ]);

    expect($fdb)->toBe([
        [self::MAC_A, portId(10), null],
        [self::MAC_B, portId(10), null],
    ]);
});

test('timos ignores entries that are not local or have no vlan', function () {
    port(10);

    $fdb = osFdb('timos', [
        '|TIMETRA-SERV-MIB::tlsFdbLocale' => [
            'TIMETRA-SERV-MIB::tlsFdbLocale[5][0:11:22:33:aa:1]' => '1',
            'TIMETRA-SERV-MIB::tlsFdbLocale[5][0:11:22:33:bb:1]' => '2', // sdp
            'TIMETRA-SERV-MIB::tlsFdbLocale[5][0:11:22:33:bb:2]' => '1', // null encap
            'TIMETRA-SERV-MIB::tlsFdbLocale[5][0:11:22:33:bb:3]' => '1', // unknown port
        ],
        '|TIMETRA-SERV-MIB::tlsFdbPortId' => [
            'TIMETRA-SERV-MIB::tlsFdbPortId[5][0:11:22:33:aa:1]' => '10',
            'TIMETRA-SERV-MIB::tlsFdbPortId[5][0:11:22:33:bb:1]' => '10',
            'TIMETRA-SERV-MIB::tlsFdbPortId[5][0:11:22:33:bb:2]' => '10',
            'TIMETRA-SERV-MIB::tlsFdbPortId[5][0:11:22:33:bb:3]' => '99',
        ],
        '|TIMETRA-SERV-MIB::tlsFdbEncapValue' => [
            'TIMETRA-SERV-MIB::tlsFdbEncapValue[5][0:11:22:33:aa:1]' => '100',
            'TIMETRA-SERV-MIB::tlsFdbEncapValue[5][0:11:22:33:bb:1]' => '100',
            'TIMETRA-SERV-MIB::tlsFdbEncapValue[5][0:11:22:33:bb:2]' => '0',
            'TIMETRA-SERV-MIB::tlsFdbEncapValue[5][0:11:22:33:bb:3]' => '100',
        ],
    ]);

    expect($fdb)->toBe([[self::MAC_A, portId(10), 100]]);
});

test('aruba uses bridge ports from the vlan context', function () {
    port(10);
    port(11);

    $fdb = osFdb('arubaos', [
        '|Q-BRIDGE-MIB::dot1qTpFdbPort' => [
            'Q-BRIDGE-MIB::dot1qTpFdbPort[100][0:11:22:33:aa:1]' => '1',
            'Q-BRIDGE-MIB::dot1qTpFdbPort[100][0:11:22:33:aa:2]' => '2',
            'Q-BRIDGE-MIB::dot1qTpFdbPort[100][0:11:22:33:aa:3]' => '11', // unmapped bridge ports are not ifIndexes
        ],
        '|BRIDGE-MIB::dot1dBasePortIfIndex' => ['BRIDGE-MIB::dot1dBasePortIfIndex[1]' => '10'],
        '100|BRIDGE-MIB::dot1dBasePortIfIndex' => ['BRIDGE-MIB::dot1dBasePortIfIndex[2]' => '11'],
    ]);

    expect($fdb)->toBe([
        [self::MAC_A, portId(10), 100],
        [self::MAC_B, portId(11), 100],
        ['00112233aa03', 0, 100],
    ]);
});

test('fortiswitch joins bridge port by value', function () {
    port(87);

    $fdb = osFdb('fortiswitch', [
        '|BRIDGE-MIB::dot1dTpFdbAddress' => ['BRIDGE-MIB::dot1dTpFdbAddress[2]' => '0:11:22:33:aa:1', 'BRIDGE-MIB::dot1dTpFdbAddress[3]' => '0:11:22:33:aa:2'],
        '|BRIDGE-MIB::dot1dTpFdbPort' => ['BRIDGE-MIB::dot1dTpFdbPort[2]' => '32781', 'BRIDGE-MIB::dot1dTpFdbPort[3]' => '32782'],
        '|BRIDGE-MIB::dot1dBasePort' => ['BRIDGE-MIB::dot1dBasePort[37]' => '32781'],
        '|BRIDGE-MIB::dot1dBasePortIfIndex' => ['BRIDGE-MIB::dot1dBasePortIfIndex[37]' => '87'],
    ]);

    // entries on unknown ports are skipped
    expect($fdb)->toBe([[self::MAC_A, portId(87), null]]);
});

test('row indexes printed with amalformed oid are detected', function () {
    port(10);

    $fdb = osFdb('generic', [
        '|BRIDGE-MIB::dot1dTpFdbAddress' => ['BRIDGE-MIB::dot1dTpFdbAddress[.1' => '0:11:22:33:aa:1'],
        '|BRIDGE-MIB::dot1dTpFdbPort' => ['BRIDGE-MIB::dot1dTpFdbPort[.1' => '1'],
        '|BRIDGE-MIB::dot1dBasePortIfIndex' => ['BRIDGE-MIB::dot1dBasePortIfIndex[1]' => '10'],
    ]);

    expect($fdb)->toBe([[self::MAC_A, portId(10), null]]);
});

test('netonix reads table by row', function () {
    port(10);

    $fdb = osFdb('netonix', [
        '|BRIDGE-MIB::dot1dTpFdbAddress' => ['BRIDGE-MIB::dot1dTpFdbAddress[1]' => '0:11:22:33:aa:1', 'BRIDGE-MIB::dot1dTpFdbAddress[2]' => '0:11:22:33:aa:2'],
        '|BRIDGE-MIB::dot1dTpFdbPort' => ['BRIDGE-MIB::dot1dTpFdbPort[1]' => '1', 'BRIDGE-MIB::dot1dTpFdbPort[2]' => '0'],
        '|BRIDGE-MIB::dot1dBasePortIfIndex' => ['BRIDGE-MIB::dot1dBasePortIfIndex[1]' => '10'],
    ]);

    expect($fdb)->toBe([[self::MAC_A, portId(10), null]]);
});

test('zynos gs1900 length prefixed qbridge index', function () {
    port(7);
    $this->device->hardware = 'GS1900-24E';

    $fdb = osFdb('zynos', [
        '|Q-BRIDGE-MIB::dot1qTpFdbPort' => ['Q-BRIDGE-MIB::dot1qTpFdbPort[100][6:0:11:22:33:aa].1' => '7'],
    ]);

    expect($fdb)->toBe([[self::MAC_A, portId(7), 100]]);
});

test('jetstream bridge ports are offset from if index', function () {
    port(49153);

    $fdb = osFdb('jetstream', [
        '|Q-BRIDGE-MIB::dot1qTpFdbPort' => ['Q-BRIDGE-MIB::dot1qTpFdbPort[100][0:11:22:33:aa:1]' => '1'],
    ]);

    expect($fdb)->toBe([[self::MAC_A, portId(49153), 100]]);
});

/**
 * Run the module with an OS that returns the given entries
 */
function discover(callable $entries): void
{
    $os = Mockery::mock(OS::class);
    $os->shouldReceive('getDevice')->andReturn($this->device);
    $os->shouldReceive('discoverFdbTable')->andReturnUsing(fn () => new Collection($entries()));

    /** @var OS $os */
    discover($os);
}

/**
 * Run the OS class discovery against fake snmp data
 *
 * @param  array<string, array<string, string>>  $walks  Responses keyed by "context|oid"
 * @return list<array{string, int, int|null}> mac_address, port_id and vlan
 */
function osFdb(string $os, array $walks): array
{
    $this->device->os = $os;
    $this->device->save();

    $this->app->bind(SnmpQueryBuilder::class, fn () => fakeSnmp($walks));

    $deviceArray = $this->device->toArray();

    return OS::make($deviceArray)->discoverFdbTable()
        ->map(fn (PortsFdb $fdb) => [$fdb->mac_address, $fdb->port_id, $fdb->vlan])
        ->all();
}

/**
 * @param  array<string, array<string, string>>  $walks
 */
function fakeSnmp(array $walks): SnmpQueryBuilder
{
    $context = '';
    $query = Mockery::mock(SnmpQueryBuilder::class);
    foreach (['cache', 'allowUnordered', 'numericIndex', 'mibDir'] as $option) {
        $query->shouldReceive($option)->andReturnSelf();
    }
    $query->shouldReceive('context')->andReturnUsing(function (string $name) use (&$context, $query) {
        $context = $name;

        return $query;
    });
    $query->shouldReceive('walk')->andReturnUsing(function (array|string $oids) use (&$context, $walks) {
        $values = [];
        foreach ((array) $oids as $oid) {
            $values += $walks["$context|$oid"] ?? [];
        }

        return new SnmpResponse($values);
    });

    return $query;
}

function port(int $ifIndex): Port
{
    return Port::factory()->create(['device_id' => $this->device->device_id, 'ifIndex' => $ifIndex]);
}

function portId(int $ifIndex): int
{
    return $this->device->ports()->where('ifIndex', $ifIndex)->value('port_id');
}

function fdb(string $mac, int $portId, ?int $vlan = null): PortsFdb
{
    return new PortsFdb(['mac_address' => $mac, 'port_id' => $portId, 'vlan' => $vlan]);
}