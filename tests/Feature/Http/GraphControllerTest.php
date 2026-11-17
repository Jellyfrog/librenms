<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use App\Graphs\Rrd\RrdFileResolver;
use App\Models\Bill;
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use LibreNMS\RRD\Backend\RrdBackendInterface;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('admin');
    LibrenmsConfig::set('auth_mechanism', 'mysql');
    LibrenmsConfig::set('webui.graph_type', 'png');
});

test('renders image', function () {
    $bill = Bill::factory()->create();

    $response = $this->actingAs(adminUser())
        ->get("/graph?type=bill_historicmonthly&id=$bill->bill_id&width=600&height=250");

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/png');
    expect($response->getContent())->toStartWith("\x89PNG");
});

test('user title may contain punctuation', function () {
    $bill = Bill::factory()->create();

    $response = $this->actingAs(adminUser())
        ->get("/graph?type=bill_historicmonthly&id=$bill->bill_id&graph_title=" . urlencode("Bob's (bill)"));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/png');
});

test('unknown graph responds like unauthorized', function () {
    $device = Device::factory()->create();

    $unknown = $this->actingAs(adminUser())->get("/graph?type=device_doesnotexist&device=$device->device_id");
    $unauthorized = $this->actingAs(User::factory()->create(['enabled' => 1]))->get("/graph?type=device_processor&device=$device->device_id");

    foreach ([$unknown, $unauthorized] as $response) {
        $response->assertForbidden();
        $response->assertHeader('Content-Type', 'image/png');
    }
    expect($unauthorized->getContent())->toBe($unknown->getContent());
});

test('invalid input responds with error image', function () {
    $device = Device::factory()->create();

    $response = $this->actingAs(adminUser())
        ->get("/graph?type=device_processor&device=$device->device_id&width=abc");

    $response->assertUnprocessable();
    $response->assertHeader('Content-Type', 'image/png');
});

test('graph error responds with error image', function () {
    $device = Device::factory()->create();

    $response = $this->actingAs(adminUser())
        ->get("/graph?type=device_processor&device=$device->device_id");

    $response->assertServerError();
    $response->assertHeader('Content-Type', 'image/png');
});

test('graph specific validation responds with error image', function () {
    $bill = Bill::factory()->create();

    $response = $this->actingAs(adminUser())
        ->get("/graph?type=bill_historictransfer&id=$bill->bill_id&imgtype=week");

    $response->assertUnprocessable();
    $response->assertHeader('Content-Type', 'image/png');
});

test('array input does not break authorization', function () {
    $response = $this->actingAs(adminUser())->get('/graph?type=bill_historicmonthly&id[]=1');

    $response->assertForbidden();
    $response->assertHeader('Content-Type', 'image/png');
});

test('legacy auth errors respond like unauthorized', function () {
    $response = $this->actingAs(adminUser())->get('/graph?type=bill_bits');

    $response->assertForbidden();
    $response->assertHeader('Content-Type', 'image/png');
});

test('graph size is limited', function () {
    $bill = Bill::factory()->create();

    $response = $this->actingAs(adminUser())
        ->get("/graph?type=bill_historicmonthly&id=$bill->bill_id&width=200000&height=200000");

    $response->assertUnprocessable();
    $response->assertHeader('Content-Type', 'image/png');
});

test('customoid with spaces and unit symbols', function () {
    $device = Device::factory()->create();
    $commands = fakeRrdBackend();

    foreach (['%' => '%25', '°C' => '%C2%B0C'] as $unit => $encoded) {
        $response = $this->actingAs(adminUser())
            ->get("/graph?type=customoid_Test%20OID&device=$device->device_id&unit=$encoded");

        $response->assertOk();
        expect($response->getContent())->toBe('image-data');
        $options = $commands[$commands->count() - 1];
        expect(preg_grep('/^DEF:.*customoid-Test_OID\.rrd:oid_value:AVERAGE$/', $options))->not->toBeEmpty();
        expect(preg_grep('/^COMMENT:' . preg_quote($unit, '/') . ' +Now/', $options))->not->toBeEmpty();
    }
});

test('legacy variables are not limited to plain characters', function () {
    $device = Device::factory()->create();
    fakeRrdBackend();

    $this->actingAs(adminUser())
        ->get("/graph?type=customoid_Test&device=$device->device_id&cert_name=" . rawurlencode('*.example.com'))
        ->assertOk();
});

test('debug shows validation errors', function () {
    $device = Device::factory()->create();

    $response = $this->actingAs(adminUser())
        ->get("/graph?type=device_processor&device=$device->device_id&width=abc&debug=1");

    $response->assertServerError();
    $this->assertNotSame('image/png', $response->headers->get('Content-Type'));
});

test('customoid unit cannot add rrdtool escapes', function () {
    $device = Device::factory()->create();
    $commands = fakeRrdBackend();

    $this->actingAs(adminUser())
        ->get("/graph?type=customoid_Test&device=$device->device_id&unit=" . urlencode('a:b\\g'))
        ->assertOk();

    expect(preg_grep('/^COMMENT:abg +Now/', $commands[0]))->not->toBeEmpty();
});

test('guests are refused without anonymous graphs', function () {
    $device = Device::factory()->create();
    fakeRrdBackend();

    $this->get("/graph?type=device_ib_dns_performance&device=$device->device_id")->assertRedirect();
    $this->get("/graph?type=device_netstat_ip&device=$device->device_id")->assertRedirect();
});

test('guests may view when anonymous graphs are allowed', function () {
    LibrenmsConfig::set('allow_unauth_graphs', true);
    $device = Device::factory()->create();
    fakeRrdBackend();

    foreach (['device_ib_dns_performance', 'device_netstat_ip'] as $type) {
        $response = $this->get("/graph?type=$type&device=$device->device_id");

        $response->assertOk();
        expect($response->getContent())->toBe('image-data');
    }
});

test('guests may view from allowed networks', function () {
    LibrenmsConfig::set('allow_unauth_graphs_cidr', ['192.0.2.0/24']);
    $device = Device::factory()->create();
    fakeRrdBackend();

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
        ->get("/graph?type=device_netstat_ip&device=$device->device_id")
        ->assertOk();
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
        ->get("/graph?type=device_netstat_ip&device=$device->device_id")
        ->assertRedirect();
});

test('signed urls are trusted', function () {
    $device = Device::factory()->create();
    fakeRrdBackend();

    foreach (['device_ib_dns_performance', 'device_netstat_ip'] as $type) {
        $response = $this->get(URL::signedRoute('graph', ['type' => $type, 'device' => $device->device_id]));

        $response->assertOk();
        expect($response->getContent())->toBe('image-data');
    }
});

test('users without permission are refused', function () {
    $device = Device::factory()->create();
    LibrenmsConfig::set('allow_unauth_graphs', true);
    fakeRrdBackend();

    foreach (['device_ib_dns_performance', 'device_netstat_ip'] as $type) {
        $this->actingAs(User::factory()->create(['enabled' => 1]))
            ->get("/graph?type=$type&device=$device->device_id")
            ->assertForbidden();
    }
});

/**
 * Draw every graph as 'image-data', as if all rrd files exist
 *
 * @return ArrayObject<int, array<int, string>> the options of each draw
 */
function fakeRrdBackend(): ArrayObject
{
    $commands = new ArrayObject;

    $rrdFiles = Mockery::mock(RrdFileResolver::class);
    $rrdFiles->shouldReceive('pathFor')->andReturn('localhost/test.rrd');
    $this->app->instance(RrdFileResolver::class, $rrdFiles);

    $backend = Mockery::mock(RrdBackendInterface::class);
    $backend->shouldReceive('graph')->andReturnUsing(function (array $options) use ($commands): string {
        $commands->append($options);

        return 'image-data';
    });
    $this->app->instance(RrdBackendInterface::class, $backend);

    return $commands;
}

function adminUser(): User
{
    return User::factory()->create(['enabled' => 1])->assignRole('admin');
}