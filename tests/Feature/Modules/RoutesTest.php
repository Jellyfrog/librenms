<?php

use App\Models\Device;
use App\Models\Route;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use LibreNMS\Data\Source\Snmp\SnmpQueryBuilder;
use LibreNMS\Data\Source\Snmp\SnmpResponse;
use LibreNMS\Interfaces\Discovery\RouteDiscovery;
use LibreNMS\Modules\Routes;
use LibreNMS\OS;

uses(LibreNMS\Tests\DBTestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function (): void {
    $this->device = Device::factory()->create(['os' => 'generic']);
    $this->app->bind(SnmpQueryBuilder::class, function () {
        $query = Mockery::mock(SnmpQueryBuilder::class);
        $query->shouldReceive('hideMib')->andReturnSelf();
        $query->shouldReceive('walk', 'get')->andReturnUsing(fn () => new SnmpResponse([]));

        return $query;
    });
});

test('observed routes update in place while history keeps its last seen time', function (): void {
    $this->travelTo(Carbon::parse('2026-01-01 00:00:00'));
    discoverRoutes([makeRoute(), makeRoute(['inetCidrRouteDest' => '10.1.0.0'])]);
    $entries = $this->device->routes()->get()->keyBy('inetCidrRouteDest');
    $original = $entries['10.0.0.0'];
    $historical = $entries['10.1.0.0'];

    $this->travelTo(Carbon::parse('2026-01-02 00:00:00'));
    discoverRoutes([makeRoute(['inetCidrRouteMetric1' => 123, 'port_id' => 42])]);

    $entries = $this->device->routes()->get()->keyBy('inetCidrRouteDest');
    expect($entries)->toHaveCount(2);
    expect($entries['10.0.0.0']->route_id)->toBe($original->route_id);
    expect($entries['10.0.0.0']->inetCidrRouteMetric1)->toBe(123);
    expect($entries['10.0.0.0']->port_id)->toBe(42);
    expect($original->created_at->equalTo($entries['10.0.0.0']->created_at))->toBeTrue();
    expect($entries['10.0.0.0']->updated_at->toDateTimeString())->toBe('2026-01-02 00:00:00');
    expect($entries['10.1.0.0']->getAttributes())->toBe($historical->getAttributes());

    $this->travelTo(Carbon::parse('2026-01-03 00:00:00'));
    discoverRoutes([makeRoute(['inetCidrRouteMetric1' => 123, 'port_id' => 42])]);
    expect($original->fresh()->updated_at->toDateTimeString())->toBe('2026-01-03 00:00:00');
});

test('composite keys distinguish contexts interfaces hops and prefixes', function (): void {
    discoverRoutes([
        makeRoute(),
        makeRoute(['context_name' => 'vrf-blue']),
        makeRoute(['inetCidrRouteIfIndex' => 11]),
        makeRoute(['inetCidrRouteNextHop' => '192.0.2.2']),
        makeRoute(['inetCidrRoutePfxLen' => 25]),
    ]);
    $ids = $this->device->routes()->orderBy('route_id')->pluck('route_id')->all();

    discoverRoutes([makeRoute(['context_name' => 'vrf-blue', 'inetCidrRouteMetric1' => 222])]);

    expect($ids)->toHaveCount(5);
    expect($this->device->routes()->orderBy('route_id')->pluck('route_id')->all())->toBe($ids);
    expect($this->device->routes()->where('context_name', 'vrf-blue')->sole()->inetCidrRouteMetric1)->toBe(222);
    expect($this->device->routes()->where('inetCidrRouteMetric1', 1)->count())->toBe(4);
});

test('empty or invalid discovery does not touch history', function (): void {
    $this->travelTo(Carbon::parse('2026-01-01 00:00:00'));
    discoverRoutes([makeRoute()]);
    $original = $this->device->routes()->sole()->getAttributes();
    $this->travelTo(Carbon::parse('2026-01-02 00:00:00'));
    discoverRoutes([]);
    discoverRoutes([makeRoute(['inetCidrRouteDest' => '']), makeRoute(['inetCidrRouteDest' => 'invalid'])]);

    expect($this->device->routes()->sole()->getAttributes())->toBe($original);
});

test('duplicates are collapsed and the last discovered route wins', function (): void {
    discoverRoutes([makeRoute()]);
    $original = $this->device->routes()->sole();
    $original->replicate()->save();

    discoverRoutes([makeRoute(['inetCidrRouteMetric1' => 20]), makeRoute(['inetCidrRouteMetric1' => 30])]);

    $entry = $this->device->routes()->sole();
    expect($entry->route_id)->toBe($original->route_id);
    expect($entry->inetCidrRouteMetric1)->toBe(30);
});

test('legacy null context matches the default context', function (): void {
    $route = makeRoute(['device_id' => $this->device->device_id, 'port_id' => 0, 'context_name' => null]);
    $route->save();

    discoverRoutes([makeRoute()]);

    $entry = $this->device->routes()->sole();
    expect($entry->route_id)->toBe($route->route_id);
    expect($entry->context_name)->toBe('');
});

test('normalization scope and explicit writable columns', function (): void {
    $other = Device::factory()->create();
    $foreign = makeRoute(['device_id' => $other->device_id, 'port_id' => 0]);
    $foreign->save();
    $this->travelTo(Carbon::parse('2026-01-02 00:00:00'));
    discoverRoutes([makeRoute([
        'device_id' => $other->device_id,
        'route_id' => $foreign->route_id,
        'created_at' => '2020-01-01 00:00:00',
        'context_name' => ' "blue" ',
        'inetCidrRouteMetric1' => -1,
        'inetCidrRouteNextHopAS' => -1,
    ])]);

    $entry = $this->device->routes()->sole();
    expect($entry->context_name)->toBe('blue');
    expect($entry->port_id)->toBe(0);
    expect($entry->inetCidrRouteMetric1)->toBe(0);
    expect($entry->inetCidrRouteNextHopAS)->toBe(4294967295);
    expect($entry->created_at->toDateTimeString())->toBe('2026-01-02 00:00:00');
    $this->assertNotSame($foreign->route_id, $entry->route_id);
    expect($foreign->fresh()->getAttributes())->toEqual($foreign->getAttributes());
});

test('persistence does not dispatch eloquent events', function (): void {
    $events = array_map(fn ($event) => "eloquent.$event: " . Route::class, ['saving', 'saved', 'creating', 'created', 'updating', 'updated', 'deleting', 'deleted']);
    Event::fake($events);

    discoverRoutes([makeRoute()]);
    discoverRoutes([makeRoute(['inetCidrRouteMetric1' => 2])]);

    foreach ($events as $event) {
        Event::assertNotDispatched($event);
    }
    expect($this->device->routes()->sole()->inetCidrRouteMetric1)->toBe(2);
});

test('varied updates use batches and preserve row ids', function (): void {
    $routes = [];
    for ($i = 1; $i <= 1005; $i++) {
        $routes[] = makeRoute(['inetCidrRouteDest' => long2ip(ip2long('10.0.0.0') + $i), 'inetCidrRouteMetric1' => $i]);
    }
    discoverRoutes($routes);
    $ids = $this->device->routes()->orderBy('route_id')->pluck('route_id')->all();
    foreach ($routes as $route) {
        $route->inetCidrRouteMetric1++;
        $route->inetCidrRoutePolicy = "quoted' ? policy";
    }

    $db = DB::connection();
    $db->enableQueryLog();
    $db->flushQueryLog();
    try {
        discoverRoutes($routes);
        expect($db->getQueryLog())->toHaveCount(4); // two reads and two updates
    } finally {
        $db->disableQueryLog();
    }

    expect($this->device->routes()->orderBy('route_id')->pluck('route_id')->all())->toBe($ids);
    expect($this->device->routes()->where('inetCidrRouteDest', '10.0.0.1')->sole()->inetCidrRouteMetric1)->toBe(2);
    expect($this->device->routes()->where('inetCidrRouteDest', long2ip(ip2long('10.0.0.0') + 1005))->sole()->inetCidrRouteMetric1)->toBe(1006);
    expect($this->device->routes()->where('inetCidrRoutePolicy', "quoted' ? policy")->count())->toBe(1005);
});

/** @param array<string, int|string|null> $attributes */
function makeRoute(array $attributes = []): Route
{
    return Route::factory()->make(array_replace([
        'context_name' => '',
        'inetCidrRouteDest' => '10.0.0.0',
        'inetCidrRouteIfIndex' => 10,
        'inetCidrRouteNextHop' => '192.0.2.1',
        'inetCidrRoutePfxLen' => 24,
        'inetCidrRouteMetric1' => 1,
        'inetCidrRouteType' => 4,
        'inetCidrRouteProto' => 3,
        'inetCidrRouteNextHopAS' => 0,
    ], $attributes));
}

/** @param list<Route> $routes */
function discoverRoutes(array $routes): void
{
    $os = Mockery::mock(OS::class, RouteDiscovery::class);
    $os->shouldReceive('getDevice')->andReturn(test()->device);
    $os->shouldReceive('discoverRoutes')->andReturn(new Collection($routes));

    (new Routes)->discover($os);
}