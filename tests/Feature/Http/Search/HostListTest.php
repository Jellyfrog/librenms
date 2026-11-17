<?php

uses(LibreNMS\Tests\Feature\Http\Search\SearchTestCase::class);
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\Ipv4Address;
use App\Models\Ipv4Mac;
use App\Models\PortsFdb;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('partial matches are listed per host', function (): void {
    [$device, $port] = $this->portOn();
    Ipv4Mac::factory()->create(['device_id' => $device->device_id, 'port_id' => $port->port_id, 'mac_address' => '02b0b0b0b001', 'ipv4_address' => '203.0.113.9']);
    Ipv4Mac::factory()->create(['device_id' => $device->device_id, 'port_id' => $port->port_id, 'mac_address' => '02b0b0b0b002', 'ipv4_address' => '203.0.113.99']);
    Ipv4Address::factory()->create(['port_id' => $port->port_id, 'ipv4_address' => '203.0.113.9', 'ipv4_prefixlen' => 28]);

    // no IP known, listed by MAC
    PortsFdb::factory()->create(['device_id' => $device->device_id, 'port_id' => $port->port_id, 'mac_address' => '02b0b0b0b003']);

    // already listed by its IP
    PortsFdb::factory()->create(['device_id' => $device->device_id, 'port_id' => $port->port_id, 'mac_address' => '02b0b0b0b001']);

    $this->results(['q' => '203.0.113.'])->assertJson(['mode' => 'list', 'total' => 2]);
    $this->results(['q' => '203.0.113.0/28'])->assertJson(['mode' => 'list', 'total' => 1]);
    $this->results(['q' => '02:b0:b0'])->assertJson(['mode' => 'list', 'total' => 3]);
});

test('empty search lists everything and pages', function (): void {
    [$device, $port] = $this->portOn();
    foreach ([1, 2, 3] as $last) {
        Ipv4Mac::factory()->create(['device_id' => $device->device_id, 'port_id' => $port->port_id, 'mac_address' => "02b1b1b1b00$last", 'ipv4_address' => "192.0.2.$last"]);
    }

    $this->results(['q' => '', 'filter' => ['device_id' => ['eq' => $device->device_id]], 'per_page' => 10])
        ->assertJson(['mode' => 'list', 'total' => 3]);
    expect($this->results([])->json('total'))->toBeGreaterThanOrEqual(3);
});

test('filters narrow the list', function (): void {
    [$first, $firstPort] = $this->portOn('first-port');
    [$second, $secondPort] = $this->portOn('second-port');
    Ipv4Mac::factory()->create(['device_id' => $first->device_id, 'port_id' => $firstPort->port_id, 'mac_address' => '02b2b2b2b001', 'ipv4_address' => '192.0.2.61']);
    Ipv4Mac::factory()->create(['device_id' => $second->device_id, 'port_id' => $secondPort->port_id, 'mac_address' => '02b2b2b2b002', 'ipv4_address' => '192.0.2.62']);
    PortsFdb::factory()->create(['device_id' => $first->device_id, 'port_id' => $firstPort->port_id, 'mac_address' => '02b2b2b2b001', 'vlan' => 342]);
    PortsFdb::factory()->create(['device_id' => $second->device_id, 'port_id' => $secondPort->port_id, 'mac_address' => '02b2b2b2b003']);

    $q = '02:b2:b2';
    $this->results(['q' => $q])->assertJson(['total' => 3]);
    $this->results(['q' => $q, 'filter' => ['device_id' => ['eq' => $first->device_id]]])->assertJson(['total' => 1]);
    $this->results(['q' => $q, 'filter' => ['port_id' => ['eq' => $secondPort->port_id]]])->assertJson(['total' => 2]);
    $this->results(['q' => $q, 'filter' => ['ifAlias' => ['contains' => 'first']]])->assertJson(['total' => 1]);
    $this->results(['q' => $q, 'filter' => ['vlan' => ['eq' => 342]]])->assertJson(['total' => 1]);
    $this->results(['q' => $q, 'filter' => ['source' => ['eq' => 'fdb']]])->assertJson(['total' => 2]);
    $this->results(['q' => $q, 'filter' => ['source' => ['in' => 'neighbor,fdb']]])->assertJson(['total' => 3]);
    $this->results(['q' => $q, 'filter' => ['source' => ['neq' => 'neighbor']]])->assertJson(['total' => 2]);
});

test('filters turn an exact search into a list unless opening the host', function (): void {
    [$device, $port] = $this->portOn();
    Ipv4Mac::factory()->create(['device_id' => $device->device_id, 'port_id' => $port->port_id, 'mac_address' => '02b3b3b3b001', 'ipv4_address' => '192.0.2.71']);

    $this->results(['q' => '192.0.2.71', 'filter' => ['source' => ['eq' => 'neighbor']]])->assertJson(['mode' => 'list', 'total' => 1]);
    $this->results(['q' => '192.0.2.71', 'filter' => ['source' => ['eq' => 'fdb']]])
        ->assertJson(['mode' => 'not_found', 'total' => 0])
        ->assertJsonPath('html', fn ($html) => str_contains($html, __('search.not_found.without_filters')) && str_contains($html, __('search.not_found.filtered')) && ! str_contains($html, __('search.not_found.title')));
    $this->results(['q' => '192.0.2.71', 'view' => 'host', 'filter' => ['source' => ['eq' => 'fdb']]])->assertJsonPath('mode', 'host');
});

test('device and port filters find hosts in the forwarding table', function (): void {
    [$router, , $switch, $switchPort] = $this->hostBehindSwitch('10.90.1.5', '02d0d0d0d001');
    [, , $otherSwitch] = $this->hostBehindSwitch('10.90.1.6', '02d0d0d0d002');

    $this->results(['q' => '', 'filter' => ['device_id' => ['eq' => $switch->device_id]]])->assertJson(['total' => 1]);
    $this->results(['q' => '', 'filter' => ['port_id' => ['eq' => $switchPort->port_id]]])->assertJson(['total' => 1]);
    $this->results(['q' => '10.90.1.0/24', 'filter' => ['device_id' => ['eq' => $switch->device_id]]])->assertJson(['mode' => 'list', 'total' => 1]);
    $this->results(['q' => '10.90.1.0/24', 'filter' => ['device_id' => ['eq' => $otherSwitch->device_id]]])->assertJson(['total' => 1]);

    // the router that learned the IP still finds it
    $this->results(['q' => '', 'filter' => ['device_id' => ['eq' => $router->device_id]]])->assertJson(['total' => 1]);
});

test('fdb source includes macs with an ip', function (): void {
    $this->hostBehindSwitch('10.91.1.5', '02d1d1d1d001');

    $this->results(['q' => '02:d1:d1', 'filter' => ['source' => ['eq' => 'fdb']]])->assertJson(['total' => 1]);

    // the forwarding table has no IPs, it finds them through the neighbor tables
    $response = $this->results(['q' => '10.91.1.0/24', 'filter' => ['source' => ['eq' => 'fdb']]])->assertJson(['mode' => 'list', 'total' => 1]);
    $this->assertStringContainsString('10.91.1.5', $response->json('html'));
});

test('pasted list keeps its order and counts not seen', function (): void {
    $this->hostBehindSwitch('10.92.1.5', '02d2d2d2d001');
    $this->hostBehindSwitch('10.92.1.6', '02d2d2d2d002');

    $response = $this->results(['q' => '10.92.1.6 10.92.1.99 10.92.1.5'])->assertJson(['mode' => 'list', 'total' => 3]);

    expect($this->listedIps($response->json('html')))->toBe(['10.92.1.6', '10.92.1.99', '10.92.1.5']);
    $this->assertStringContainsString(__('search.list.not_seen', ['count' => 1]), $response->json('summary'));

    // sorting by a column replaces the pasted order
    $sorted = $this->results(['q' => '10.92.1.6 10.92.1.99 10.92.1.5', 'sort' => 'ip']);
    expect($this->listedIps($sorted->json('html')))->toBe(['10.92.1.5', '10.92.1.6', '10.92.1.99']);
});

test('pasted mac with an ip is listed once', function (): void {
    $this->hostBehindSwitch('10.93.1.5', '02d3d3d3d001');

    $this->results(['q' => '02:d3:d3:d3:d0:01 10.93.1.5'])->assertJson(['total' => 1]);
});

test('long pasted list says what was not searched', function (): void {
    $list = implode(' ', array_map(fn ($last) => "192.0.2.$last", range(1, 60)));

    $response = $this->results(['q' => $list])->assertJson(['total' => 50]);

    $this->assertStringContainsString(__('search.list.terms_ignored', ['count' => 50, 'total' => 60]), $response->json('summary'));
});

test('network search counts addresses seen', function (): void {
    $this->hostBehindSwitch('10.94.1.5', '02d4d4d4d001');

    expect($this->results(['q' => '10.94.1.0/24'])->json('summary'))->toBe(__('search.list.network_seen', ['count' => 1, 'size' => '254']));
});

test('mac search has one row for each mac', function (): void {
    [$router, $port] = $this->portOn();
    foreach ([5, 6, 7] as $last) {
        Ipv4Mac::factory()->create(['device_id' => $router->device_id, 'port_id' => $port->port_id, 'mac_address' => '02d5d5d5d001', 'ipv4_address' => "10.95.1.$last"]);
    }

    $response = $this->results(['q' => '02:d5:d5'])->assertJson(['mode' => 'list', 'total' => 1]);

    $this->assertStringContainsString(__('search.host.more', ['count' => 2]), $response->json('html'));
});

test('list sorts by last seen with unknown last', function (): void {
    [$router, $port] = $this->portOn();
    foreach (['old' => 5, 'new' => 6, 'unknown' => 7] as $age => $last) {
        $mac = "02d6d6d6d00$last";
        Ipv4Mac::factory()->create(['device_id' => $router->device_id, 'port_id' => $port->port_id, 'mac_address' => $mac, 'ipv4_address' => "10.96.1.$last"]);
        if ($age !== 'unknown') {
            $entry = PortsFdb::factory()->create(['device_id' => $router->device_id, 'port_id' => $port->port_id, 'mac_address' => $mac]);
            PortsFdb::whereKey($entry->getKey())->toBase()->update(['updated_at' => $age === 'old' ? now()->subDays(9) : now()]);
        }
    }

    $oldest = $this->results(['q' => '10.96.1.0/24', 'sort' => 'seen', 'dir' => 'asc']);
    expect($this->listedIps($oldest->json('html')))->toBe(['10.96.1.5', '10.96.1.6', '10.96.1.7']);
    $newest = $this->results(['q' => '10.96.1.0/24', 'sort' => 'seen', 'dir' => 'desc']);
    expect($this->listedIps($newest->json('html')))->toBe(['10.96.1.6', '10.96.1.5', '10.96.1.7']);
});

test('list uses standard page links to the search page', function (): void {
    [$router, $port] = $this->portOn();
    foreach (range(1, 12) as $last) {
        Ipv4Mac::factory()->create(['device_id' => $router->device_id, 'port_id' => $port->port_id, 'mac_address' => sprintf('02d7d7d7d0%02d', $last), 'ipv4_address' => "10.97.1.$last"]);
    }

    $response = $this->results(['q' => '10.97.1.0/24', 'per_page' => 10, 'sort' => 'mac', 'dir' => 'desc', 'page' => 2]);

    expect($this->listedIps($response->json('html')))->toHaveCount(2);
    $this->assertStringContainsString(e(route('search', ['q' => '10.97.1.0/24', 'per_page' => 10, 'sort' => 'mac', 'dir' => 'desc', 'page' => 1])), $response->json('html'));
});

test('list shows the neighbor interface without a forwarding entry', function (): void {
    [$router, $port] = $this->portOn('edge-link');
    Ipv4Mac::factory()->create(['device_id' => $router->device_id, 'port_id' => $port->port_id, 'mac_address' => '02d8d8d8d001', 'ipv4_address' => '10.98.1.5']);

    $html = $this->results(['q' => '10.98.1.0/24'])->json('html');

    $this->assertStringContainsString(e(__('search.list.via_help')), $html);
    $this->assertStringContainsString('edge-link', $html);
});

test('list shows where a monitored host is plugged in', function (): void {
    [, , , $switchPort] = $this->hostBehindSwitch('10.99.1.5', '02d9d9d9d001');
    [, $own] = $this->portOn('own-interface');
    Ipv4Address::factory()->create(['port_id' => $own->port_id, 'ipv4_address' => '10.99.1.5', 'ipv4_prefixlen' => 24]);

    $html = $this->results(['q' => '10.99.1.0/24'])->json('html');

    $this->assertStringContainsString('own-interface', $html);
    $this->assertStringContainsString($switchPort->getLabel(), $html);
});

test('vendor search', function (): void {
    LibrenmsConfig::set('mac_oui.enabled', true);
    DB::table('vendor_ouis')->insert(['oui' => 'f0f0f0', 'vendor' => 'Searchtest Widgets Inc']);
    [$device, $port] = $this->portOn();
    Ipv4Mac::factory()->create(['device_id' => $device->device_id, 'port_id' => $port->port_id, 'mac_address' => 'f0f0f0123456', 'ipv4_address' => '192.0.2.44']);

    $this->results(['q' => 'searchtest widgets'])
        ->assertJson(['mode' => 'list', 'total' => 1])
        ->assertJsonPath('html', fn ($html) => str_contains($html, 'Searchtest Widgets Inc'));
});

test('devices are found by text', function (): void {
    $device = Device::factory()->create(['hostname' => 'search-core-sw1.example.com', 'ip' => '198.51.100.20']);

    $this->results(['q' => 'search-core'])
        ->assertJson(['mode' => 'list', 'total' => 1])
        ->assertJsonPath('html', fn ($html) => str_contains($html, '198.51.100.20'));
    $this->results(['q' => 'search-core', 'filter' => ['source' => ['eq' => 'neighbor']]])->assertJson(['total' => 0]);
    expect(Device::where('hostname', 'search-core-sw1.example.com')->value('device_id'))->toBe($device->device_id);
});

test('pasted addresses list each host including not seen', function (): void {
    [$device, $port] = $this->portOn();
    Ipv4Mac::factory()->create(['device_id' => $device->device_id, 'port_id' => $port->port_id, 'mac_address' => '02b4b4b4b001', 'ipv4_address' => '192.0.2.81']);

    $response = $this->results(['q' => "192.0.2.81\n192.0.2.82"])->assertJson(['mode' => 'list', 'total' => 2]);

    $this->assertStringContainsString(__('search.host.not_seen'), $response->json('html'));
});

test('long pasted list is accepted', function (): void {
    $list = implode(' ', array_map(fn ($last) => "192.0.2.$last", range(1, 40)));
    expect(strlen($list))->toBeGreaterThan(255);

    $this->results(['q' => $list])->assertJson(['mode' => 'list', 'total' => 40]);
});

test('lists respect access', function (): void {
    [$visible, $visiblePort] = $this->portOn();
    [$hidden, $hiddenPort] = $this->portOn();
    Ipv4Mac::factory()->create(['device_id' => $visible->device_id, 'port_id' => $visiblePort->port_id, 'mac_address' => '02b5b5b5b001', 'ipv4_address' => '192.0.2.91']);
    Ipv4Mac::factory()->create(['device_id' => $hidden->device_id, 'port_id' => $hiddenPort->port_id, 'mac_address' => '02b5b5b5b002', 'ipv4_address' => '192.0.2.92']);

    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');
    $user->devicesOwned()->attach($visible->device_id);

    $this->results(['q' => '192.0.2.'], $user)->assertJson(['total' => 1]);
    $this->results(['q' => '192.0.2.92'], $user)->assertJsonPath('mode', 'not_found');
});

test('vlan filter ignores fdb entries the user cannot access', function (): void {
    [$visible, $visiblePort] = $this->portOn();
    [$hidden, $hiddenPort] = $this->portOn();
    Ipv4Mac::factory()->create(['device_id' => $visible->device_id, 'port_id' => $visiblePort->port_id, 'mac_address' => '02b6b6b6b001', 'ipv4_address' => '192.0.2.93']);
    PortsFdb::factory()->create(['device_id' => $hidden->device_id, 'port_id' => $hiddenPort->port_id, 'mac_address' => '02b6b6b6b001', 'vlan' => 342]);

    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');
    $user->devicesOwned()->attach($visible->device_id);

    $this->results(['q' => '192.0.2.'], $user)->assertJson(['total' => 1]);
    $this->results(['q' => '192.0.2.', 'filter' => ['vlan' => ['eq' => 342]]], $user)->assertJsonMissing(['total' => 1]);
});

test('rows that tie have a stable order', function (): void {
    [$device, $port] = $this->portOn();
    foreach (['02e2e2e20003', '02e2e2e20001', '02e2e2e20002'] as $mac) {
        $entry = PortsFdb::factory()->create(['device_id' => $device->device_id, 'port_id' => $port->port_id, 'mac_address' => $mac]);
        PortsFdb::whereKey($entry->getKey())->toBase()->update(['updated_at' => now()->subHour()->startOfHour()]);
    }

    $html = $this->results(['q' => '02:e2:e2', 'sort' => 'seen'])->json('html');

    preg_match_all("/highlightMac\\('([^']+)'\\)/", $html, $macs);
    expect($macs[1])->toBe(['02e2e2e20001', '02e2e2e20002', '02e2e2e20003']);
});

test('pasted macs count those not seen', function (): void {
    [$router, $port] = $this->portOn();
    foreach ([5, 6] as $last) {
        Ipv4Mac::factory()->create(['device_id' => $router->device_id, 'port_id' => $port->port_id, 'mac_address' => '02e4e4e4e001', 'ipv4_address' => "10.73.1.$last"]);
    }

    $summary = $this->results(['q' => '02:e4:e4:e4:e0:01 02:e4:e4:e4:e0:02'])->assertJson(['total' => 2])->json('summary');

    $this->assertStringContainsString(__('search.list.not_seen', ['count' => 1]), $summary);
});

test('wildcards in a search are literal', function (): void {
    Device::factory()->create(['hostname' => 'swx1-wildcard', 'ip' => '198.51.100.24']);

    $this->results(['q' => 'swx1-wildcard'])->assertJson(['total' => 1]);
    $this->results(['q' => 'sw_1-wildcard'])->assertJsonPath('mode', 'not_found');
    $this->results(['q' => '%'])->assertJsonPath('mode', 'not_found');
});

test('vlan filter supports the filter operators', function (): void {
    [$device, $port] = $this->portOn();
    PortsFdb::factory()->create(['device_id' => $device->device_id, 'port_id' => $port->port_id, 'mac_address' => '02e8e8e8e001', 'vlan' => 342]);
    PortsFdb::factory()->create(['device_id' => $device->device_id, 'port_id' => $port->port_id, 'mac_address' => '02e8e8e8e002', 'vlan' => 343]);

    foreach (['in' => '342', 'eq' => '342', 'lt' => '343'] as $operator => $value) {
        $this->results(['q' => '02:e8:e8', 'filter' => ['vlan' => [$operator => $value]]])->assertJson(['total' => 1]);
    }
});

test('vlan filters exclude unknown vlans', function (string $operator, string $value, int $expected): void {
    [$device, $port] = $this->portOn();
    foreach (['02e8e8e8e003' => 342, '02e8e8e8e004' => null] as $mac => $vlan) {
        PortsFdb::factory()->create(['device_id' => $device->device_id, 'port_id' => $port->port_id, 'mac_address' => $mac, 'vlan' => $vlan]);
        Ipv4Mac::factory()->create(['device_id' => $device->device_id, 'port_id' => $port->port_id, 'mac_address' => $mac]);
    }

    $this->results(['q' => '02:e8:e8'])->assertJson(['total' => 2]);
    foreach (['fdb', 'neighbor'] as $source) {
        $this->results(['q' => '02:e8:e8', 'filter' => ['source' => ['eq' => $source], 'vlan' => [$operator => $value]]])
            ->assertJsonPath('total', $expected);
    }
})->with('unknownVlanFilters');

/**
 * @return array<string, array{string, string, int}>
 */
dataset('unknownVlanFilters', function () {
    return [
        'less than' => ['lt', '343', 1],
        'less than or equal' => ['lte', '342', 1],
        'not equal' => ['neq', '343', 1],
        'not in' => ['not_in', '343', 1],
        'equal to zero' => ['eq', '0', 0],
        'in zero' => ['in', '0', 0],
        'unknown VLAN' => ['is_empty', '1', 1],
        'known VLAN' => ['is_not_empty', '1', 1],
    ];
});

test('network search stays in its address family', function (): void {
    Device::factory()->create(['hostname' => 'v6-family.example.test', 'ip' => '2001:db8::99']);
    Device::factory()->create(['hostname' => 'v4-family.example.test', 'ip' => '32.1.13.184']);

    $this->results(['q' => '32.1.13.0/24'])->assertJson(['mode' => 'list', 'total' => 1]);

    // the same leading bytes as 2001:db8::99
    $this->results(['q' => '32.1.0.0/16'])->assertJson(['mode' => 'list', 'total' => 1]);
    $this->assertStringNotContainsString('2001:db8::99', $this->results(['q' => '32.1.0.0/16'])->json('html'));
    $this->assertStringNotContainsString('32.1.13.184', $this->results(['q' => '2001::/16'])->json('html'));
});

test('not seen count handles a row without an address', function (): void {
    $device = Device::factory()->create(['hostname' => '10.71.1.5']);
    Device::whereKey($device->device_id)->toBase()->update(['ip' => '']);

    // the device's row has no IP or MAC to list it by, both pasted addresses are unknown
    $summary = $this->results(['q' => '10.71.1.5 10.71.1.6'])->json('summary');

    $this->assertStringContainsString(__('search.list.not_seen', ['count' => 2]), $summary);
});

test('pasted macs and ips keep their order', function (): void {
    $this->hostBehindSwitch('10.68.1.5', '02e9e9e9e001');
    $this->hostBehindSwitch('10.68.1.6', '02e9e9e9e002');

    $response = $this->results(['q' => '10.68.1.6 02:e9:e9:e9:e0:01 10.68.1.99'])->assertJson(['total' => 3]);

    expect($this->listedIps($response->json('html')))->toBe(['10.68.1.6', '10.68.1.5', '10.68.1.99']);
});

test('vendor search finds interface macs', function (): void {
    LibrenmsConfig::set('mac_oui.enabled', true);
    DB::table('vendor_ouis')->insert(['oui' => 'f0f0f2', 'vendor' => 'Interfacetest Widgets Inc']);
    [, $port] = $this->portOn();
    $port->update(['ifPhysAddress' => 'f0f0f2123456']);

    $this->results(['q' => 'interfacetest widgets', 'filter' => ['source' => ['eq' => 'interface']]])->assertJson(['mode' => 'list', 'total' => 1]);
});
