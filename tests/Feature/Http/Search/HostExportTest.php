<?php

uses(\LibreNMS\Tests\Feature\Http\Search\SearchTestCase::class);
use App\Models\Ipv4Mac;
use App\Models\User;
use App\Models\UserPref;
use Illuminate\Support\Facades\DB;


/**
 * @param  array<string, mixed>  $params
 * @return list<array<string, string>>
 */
function exported(array $params, ?User $user = null): array
{
    $response = $this->actingAs($user ?? $this->admin())->get(route('search.export', $params))->assertOk();
    $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));

    $lines = array_map(fn (string $line): array => str_getcsv($line, ',', '"', '\\'), explode("\n", trim(ltrim($response->streamedContent(), "\xEF\xBB\xBF"))));
    $headers = array_shift($lines);

    return array_map(fn (array $line): array => array_combine($headers, $line), $lines);
}

test('export has where each host is', function () {
    [, $routerPort, $switch, $switchPort] = $this->hostBehindSwitch('10.87.1.5', '02c9c9c9c001');
    Ipv4Mac::factory()->create(['device_id' => $routerPort->device_id, 'port_id' => $routerPort->port_id, 'mac_address' => '02c9c9c9c002', 'ipv4_address' => '10.87.1.6']);

    $rows = exported(['q' => '10.87.1.0/24', 'export' => 'all']);

    expect(array_column($rows, __('search.export.ip')))->toBe(['10.87.1.5', '10.87.1.6']);
    expect($rows[0][__('search.export.mac')])->toBe('02:c9:c9:c9:c0:01');
    expect($rows[0][__('search.export.switch')])->toBe($switch->display);
    expect($rows[0][__('search.export.port')])->toBe($switchPort->getLabel());
    expect($rows[0][__('search.export.macs_on_port')])->toBe('1');
    $this->assertNotSame('', $rows[0][__('search.export.last_seen')]);

    // no forwarding entry, the router that knows it is all there is
    expect($rows[1][__('search.export.switch')])->toBe('');
    $this->assertStringContainsString($routerPort->getLabel(), $rows[1][__('search.export.seen_via')]);
});

test('export all ignores pages and export page keeps to one', function () {
    [$router, $port] = $this->portOn();
    foreach (range(1, 12) as $last) {
        Ipv4Mac::factory()->create(['device_id' => $router->device_id, 'port_id' => $port->port_id, 'mac_address' => sprintf('02cacacac0%02d', $last), 'ipv4_address' => "10.86.1.$last"]);
    }

    expect(exported(['q' => '10.86.1.0/24', 'export' => 'all', 'page' => 2, 'perPage' => 10]))->toHaveCount(12);

    $page = exported(['q' => '10.86.1.0/24', 'export' => 'page', 'page' => 2, 'perPage' => 10]);
    expect(array_column($page, __('search.export.ip')))->toBe(['10.86.1.11', '10.86.1.12']);
});

test('export follows filters sort and mac format', function () {
    $admin = $this->admin();
    UserPref::setPref($admin, 'network_search_mac_format', 'dot');
    [, , $switch] = $this->hostBehindSwitch('10.85.1.5', '02cbcbcbc001');
    $this->hostBehindSwitch('10.85.1.6', '02cbcbcbc002');

    $filtered = exported(['q' => '10.85.1.0/24', 'filter' => ['device_id' => ['eq' => $switch->device_id]]], $admin);
    expect(array_column($filtered, __('search.export.ip')))->toBe(['10.85.1.5']);
    expect($filtered[0][__('search.export.mac')])->toBe('02cb.cbcb.c001');

    $sorted = exported(['q' => '10.85.1.0/24', 'sort' => 'ip', 'dir' => 'desc'], $admin);
    expect(array_column($sorted, __('search.export.ip')))->toBe(['10.85.1.6', '10.85.1.5']);
});

test('export has arow for each mac of an address and all ips of amac', function () {
    [$first, $firstPort] = $this->portOn();
    [$second, $secondPort] = $this->portOn();
    Ipv4Mac::factory()->create(['device_id' => $first->device_id, 'port_id' => $firstPort->port_id, 'mac_address' => '02cccccc0001', 'ipv4_address' => '10.84.2.5']);
    Ipv4Mac::factory()->create(['device_id' => $second->device_id, 'port_id' => $secondPort->port_id, 'mac_address' => '02cccccc0002', 'ipv4_address' => '10.84.2.5']);
    Ipv4Mac::factory()->create(['device_id' => $second->device_id, 'port_id' => $secondPort->port_id, 'mac_address' => '02cddddd0001', 'ipv4_address' => '10.84.2.6']);
    Ipv4Mac::factory()->create(['device_id' => $second->device_id, 'port_id' => $secondPort->port_id, 'mac_address' => '02cddddd0001', 'ipv4_address' => '10.84.2.7']);

    // the address nothing knows about still has a row
    expect(array_column(exported(['q' => '10.84.2.5 10.84.2.99']), __('search.export.mac')))->toBe(['02:cc:cc:cc:00:01', '02:cc:cc:cc:00:02', '']);
    expect(array_column(exported(['q' => '02:cd:dd']), __('search.export.ip')))->toBe(['10.84.2.6 10.84.2.7']);
});

test('export respects access', function () {
    [$hidden, $hiddenPort] = $this->portOn();
    Ipv4Mac::factory()->create(['device_id' => $hidden->device_id, 'port_id' => $hiddenPort->port_id, 'mac_address' => '02cecececc01', 'ipv4_address' => '10.83.2.5']);
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    expect(exported(['q' => '10.83.2.0/24'], $user))->toBe([]);
});

test('export neutralizes formulas', function () {
    [, , , $switchPort] = $this->hostBehindSwitch('10.72.1.5', '02e5e5e5e001');
    $switchPort->update(['ifAlias' => '=HYPERLINK("http://example.test")']);

    $rows = exported(['q' => '10.72.1.5 10.72.1.6']);

    expect($rows[0][__('search.export.port_description')])->toBe('\'=HYPERLINK("http://example.test")');
});

test('export all runs the list query once', function () {
    [$router, $port] = $this->portOn();
    Ipv4Mac::insert(array_map(fn (int $i): array => ['device_id' => $router->device_id, 'port_id' => $port->port_id, 'mac_address' => sprintf('02e7e7e7%04x', $i), 'ipv4_address' => '10.69.' . intdiv($i, 256) . '.' . ($i % 256), 'context_name' => ''], range(1, 501)));

    DB::enableQueryLog();
    $rows = exported(['q' => '10.69.0.0/16', 'export' => 'all']);

    expect($rows)->toHaveCount(501);
    expect(array_filter(DB::getQueryLog(), fn (array $query): bool => str_contains($query['query'], 'as `hosts`')))->toHaveCount(1);
});
