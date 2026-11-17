<?php

uses(\LibreNMS\Tests\DBTestCase::class);
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

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    $this->device = Device::factory()->create(['os' => 'generic']);
    $this->app->bind(SnmpQueryBuilder::class, function () {
        $query = Mockery::mock(SnmpQueryBuilder::class);
        $query->shouldReceive('hideMib')->andReturnSelf();
        $query->shouldReceive('walk', 'get')->andReturnUsing(fn () => new SnmpResponse([]));

        return $query;
    });
});

test('observed routes update in place while history keeps its last seen time', function () {
    $this->travelTo(Carbon::parse('2026-01-01 00:00:00'));
    discover([route(), route(['inetCidrRouteDest' => '10.1.0.0'])]);
    $entries = $this->device->routes()->get()->keyBy('inetCidrRouteDest');
    $original = $entries['10.0.0.0'];
    $historical = $entries['10.1.0.0'];

    $this->travelTo(Carbon::parse('2026-01-02 00:00:00'));
    discover([route(['inetCidrRouteMetric1' => 123, 'port_id' => 42])]);

    $entries = $this->device->routes()->get()->keyBy('inetCidrRouteDest');
    expect($entries)->toHaveCount(2);
    expect($entries['10.0.0.0']->route_id)->toBe($original->route_id);
    expect($entries['10.0.0.0']->inetCidrRouteMetric1)->toBe(123);
    expect($entries['10.0.0.0']->port_id)->toBe(42);
    expect($original->created_at->equalTo($entries['10.0.0.0']->created_at))->toBeTrue();
    expect($entries['10.0.0.0']->updated_at->toDateTimeString())->toBe('2026-01-02 00:00:00');
    expect($entries['10.1.0.0']->getAttributes())->toBe($historical->getAttributes());

    $this->travelTo(Carbon::parse('2026-01-03 00:00:00'));
    discover([route(['inetCidrRouteMetric1' => 123, 'port_id' => 42])]);
    expect($original->fresh()->updated_at->toDateTimeString())->toBe('2026-01-03 00:00:00');
});

test('composite keys distinguish contexts interfaces hops and prefixes', function () {
    discover([
        route(),
        route(['context_name' => 'vrf-blue']),
        route(['inetCidrRouteIfIndex' => 11]),
        route(['inetCidrRouteNextHop' => '192.0.2.2']),
        route(['inetCidrRoutePfxLen' => 25]),
    ]);
    $ids = $this->device->routes()->orderBy('route_id')->pluck('route_id')->all();

    discover([route(['context_name' => 'vrf-blue', 'inetCidrRouteMetric1' => 222])]);

    expect($ids)->toHaveCount(5);
    expect($this->device->routes()->orderBy('route_id')->pluck('route_id')->all())->toBe($ids);
    expect($this->device->routes()->where('context_name', 'vrf-blue')->sole()->inetCidrRouteMetric1)->toBe(222);
    expect($this->device->routes()->where('inetCidrRouteMetric1', 1)->count())->toBe(4);
});

test('empty or invalid discovery does not touch history', function () {
    $this->travelTo(Carbon::parse('2026-01-01 00:00:00'));
    discover([route()]);
    $original = $this->device->routes()->sole()->getAttributes();
    $this->travelTo(Carbon::parse('2026-01-02 00:00:00'));
    discover([]);
    discover([route(['inetCidrRouteDest' => '']), route(['inetCidrRouteDest' => 'invalid'])]);

    expect($this->device->routes()->sole()->getAttributes())->toBe($original);
});

test('duplicates are collapsed and the last discovered route wins', function () {
    discover([route()]);
    $original = $this->device->routes()->sole();
    $original->replicate()->save();

    discover([route(['inetCidrRouteMetric1' => 20]), route(['inetCidrRouteMetric1' => 30])]);

    $entry = $this->device->routes()->sole();
    expect($entry->route_id)->toBe($original->route_id);
    expect($entry->inetCidrRouteMetric1)->toBe(30);
});

test('legacy null context matches the default context', function () {
    $route = route(['device_id' => $this->device->device_id, 'port_id' => 0, 'context_name' => null]);
    $route->save();

    discover([route()]);

    $entry = $this->device->routes()->sole();
    expect($entry->route_id)->toBe($route->route_id);
    expect($entry->context_name)->toBe('');
});

test('normalization scope and explicit writable columns', function () {
    $other = Device::factory()->create();
    $foreign = route(['device_id' => $other->device_id, 'port_id' => 0]);
    $foreign->save();
    $this->travelTo(Carbon::parse('2026-01-02 00:00:00'));
    discover([route([
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

test('persistence does not dispatch eloquent events', function () {
    $events = array_map(fn ($event) => "eloquent.$event: " . Route::class, ['saving', 'saved', 'creating', 'created', 'updating', 'updated', 'deleting', 'deleted']);
    Event::fake($events);

    discover([route()]);
    discover([route(['inetCidrRouteMetric1' => 2])]);

    foreach ($events as $event) {
        Event::assertNotDispatched($event);
    }
    expect($this->device->routes()->sole()->inetCidrRouteMetric1)->toBe(2);
});

test('varied updates use batches and preserve row ids', function () {
    $routes = [];
    for ($i = 1; $i <= 1005; $i++) {
        $routes[] = route(['inetCidrRouteDest' => long2ip(ip2long('10.0.0.0') + $i), 'inetCidrRouteMetric1' => $i]);
    }
    discover($routes);
    $ids = $this->device->routes()->orderBy('route_id')->pluck('route_id')->all();
    foreach ($routes as $route) {
        $route->inetCidrRouteMetric1++;
        $route->inetCidrRoutePolicy = "quoted' ? policy";
    }

    $db = DB::connection();
    $db->enableQueryLog();
    $db->flushQueryLog();
    try {
        discover($routes);
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
function route(array $attributes = []): Route
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
function discover(array $routes): void
{
    $os = Mockery::mock(OS::class, RouteDiscovery::class);
    $os->shouldReceive('getDevice')->andReturn($this->device);
    $os->shouldReceive('discoverRoutes')->andReturn(new Collection($routes));

    discover($os);
}