<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\AccessPoint;
use App\Models\Device;
use App\Models\User;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');
});

test('authorized user can render access points tab', function () {
    $device = Device::factory()->create();
    AccessPoint::factory()->for($device)->create();

    $this->actingAs(admin())
        ->get(route('device', ['device' => $device, 'tab' => 'accesspoints']))
        ->assertOk()
        ->assertSee('id="access-points"', false)
        ->assertSee(route('table.access-points'), false);
});

test('endpoint returns only requested device and excludes deleted radios', function () {
    $device = Device::factory()->create();
    $otherDevice = Device::factory()->create();
    AccessPoint::factory()->for($device)->create(['name' => 'Visible AP']);
    AccessPoint::factory()->for($device)->create(['name' => 'Deleted AP', 'deleted' => true]);
    AccessPoint::factory()->for($otherDevice)->create(['name' => 'Other Device AP']);

    $response = $this->actingAs(admin())->postJson(route('table.access-points'), tableRequest($device));

    $response->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonCount(1, 'rows');
    $this->assertStringContainsString('Visible AP', $response->json('rows.0.name'));
    $this->assertStringNotContainsString('Deleted AP', $response->getContent());
    $this->assertStringNotContainsString('Other Device AP', $response->getContent());
});

test('endpoint forbids adevice the user cannot access', function () {
    $allowedDevice = Device::factory()->create();
    $deniedDevice = Device::factory()->create();
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');
    $user->devicesOwned()->attach($allowedDevice);

    $this->actingAs($user)
        ->postJson(route('table.access-points'), tableRequest($deniedDevice))
        ->assertForbidden();
});

test('search matches name and mac address', function () {
    $device = Device::factory()->create();
    AccessPoint::factory()->for($device)->create([
        'name' => 'Conference-West',
        'mac_addr' => 'aa:bb:cc:11:22:33',
    ]);
    AccessPoint::factory()->for($device)->create([
        'name' => 'Lobby-East',
        'mac_addr' => '44:55:66:77:88:99',
    ]);

    $nameResponse = $this->actingAs(admin())->postJson(route('table.access-points'), [
        ...tableRequest($device),
        'searchPhrase' => 'ference-We',
    ]);
    $macResponse = $this->postJson(route('table.access-points'), [
        ...tableRequest($device),
        'searchPhrase' => '55:66:77',
    ]);

    $nameResponse->assertOk()->assertJsonPath('total', 1);
    $this->assertStringContainsString('Conference-West', $nameResponse->json('rows.0.name'));
    $macResponse->assertOk()->assertJsonPath('total', 1);
    $this->assertStringContainsString('Lobby-East', $macResponse->json('rows.0.name'));
});

test('client sorting is numeric', function () {
    $device = Device::factory()->create();
    AccessPoint::factory()->for($device)->create(['name' => 'Two', 'numasoclients' => 2]);
    AccessPoint::factory()->for($device)->create(['name' => 'Ten', 'numasoclients' => 10]);
    AccessPoint::factory()->for($device)->create(['name' => 'Hundred', 'numasoclients' => 100]);

    $response = $this->actingAs(admin())->postJson(route('table.access-points'), [
        ...tableRequest($device),
        'sort' => ['numasoclients' => 'desc'],
    ]);

    $response->assertOk();
    expect(array_column($response->json('rows'), 'numasoclients'))->toBe([100, 10, 2]);
});

test('default ordering uses name then radio number', function () {
    $device = Device::factory()->create();
    $bravo = AccessPoint::factory()->for($device)->create(['name' => 'Bravo', 'radio_number' => 0]);
    $alphaTwo = AccessPoint::factory()->for($device)->create(['name' => 'Alpha', 'radio_number' => 2]);
    $alphaZero = AccessPoint::factory()->for($device)->create(['name' => 'Alpha', 'radio_number' => 0]);

    $response = $this->actingAs(admin())->postJson(route('table.access-points'), tableRequest($device));

    $response->assertOk();
    expect(array_column($response->json('rows'), 'accesspoint_id'))->toBe([$alphaZero->accesspoint_id, $alphaTwo->accesspoint_id, $bravo->accesspoint_id]);
});

test('endpoint paginates in the query', function () {
    $device = Device::factory()->create();
    AccessPoint::factory()->for($device)->create(['name' => 'Alpha']);
    AccessPoint::factory()->for($device)->create(['name' => 'Bravo']);
    $charlie = AccessPoint::factory()->for($device)->create(['name' => 'Charlie']);

    $response = $this->actingAs(admin())->postJson(route('table.access-points'), [
        ...tableRequest($device),
        'current' => 2,
        'rowCount' => 2,
    ]);

    $response->assertOk()
        ->assertJsonPath('current', 2)
        ->assertJsonPath('rowCount', 1)
        ->assertJsonPath('total', 3)
        ->assertJsonCount(1, 'rows')
        ->assertJsonPath('rows.0.accesspoint_id', $charlie->accesspoint_id);
});

test('snmp strings are escaped in list and detail views', function () {
    $device = Device::factory()->create();
    $accessPoint = AccessPoint::factory()->for($device)->create([
        'name' => '<script>alert("ap")</script>',
        'mac_addr' => '<b>aa:bb</b>',
        'type' => '<em>ax</em>',
        'radio_number' => 0,
    ]);

    $listResponse = $this->actingAs(admin())->postJson(route('table.access-points'), tableRequest($device));
    $listResponse->assertOk();
    $this->assertStringContainsString('&lt;script&gt;alert(&quot;ap&quot;)&lt;/script&gt;', $listResponse->json('rows.0.name'));
    $this->assertStringNotContainsString('<script>', $listResponse->json('rows.0.name'));
    expect($listResponse->json('rows.0.radio'))->toBe('&lt;em&gt;ax&lt;/em&gt; (0)');

    $detailUrl = route('device.accesspoints.show', [
        'device' => $device,
        'accessPoint' => $accessPoint,
    ]);

    $this->assertStringContainsString($detailUrl, $listResponse->json('rows.0.name'));
    $this->assertStringContainsString($detailUrl, $listResponse->json('rows.0.trends'));
    $this->assertStringNotContainsString('ap=', $listResponse->json('rows.0.name'));
    $this->assertStringNotContainsString('ap=', $listResponse->json('rows.0.trends'));

    $this->get($detailUrl)
        ->assertOk()
        ->assertSee('&lt;script&gt;alert(&quot;ap&quot;)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert("ap")</script>', false)
        ->assertSee('&lt;b&gt;aa:bb&lt;/b&gt;', false)
        ->assertSee('&lt;em&gt;ax&lt;/em&gt;', false);
});

test('modern route renders access point detail', function () {
    $device = Device::factory()->create();
    $accessPoint = AccessPoint::factory()->for($device)->create(['name' => 'Selected AP']);

    $this->actingAs(admin())
        ->get(route('device.accesspoints.show', [
            'device' => $device,
            'accessPoint' => $accessPoint,
        ]))
        ->assertOk()
        ->assertSee('Selected AP')
        ->assertSee('All access points')
        ->assertDontSee('id="access-points"', false);
});

test('detail selection cannot cross device boundary', function () {
    $device = Device::factory()->create();
    $otherDevice = Device::factory()->create();
    AccessPoint::factory()->for($device)->create(['name' => 'Local AP']);
    $foreignAccessPoint = AccessPoint::factory()->for($otherDevice)->create(['name' => 'Foreign Secret AP']);

    $this->actingAs(admin())
        ->get(route('device.accesspoints.show', [
            'device' => $device,
            'accessPoint' => $foreignAccessPoint,
        ]))
        ->assertNotFound();
});

test('deleted access point detail is not found', function () {
    $device = Device::factory()->create();
    $accessPoint = AccessPoint::factory()->for($device)->create(['deleted' => true]);

    $this->actingAs(admin())
        ->get(route('device.accesspoints.show', [
            'device' => $device,
            'accessPoint' => $accessPoint,
        ]))
        ->assertNotFound();
});

function admin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

/** @return array<string, mixed> */
function tableRequest(Device $device): array
{
    return [
        'device_id' => $device->device_id,
        'current' => 1,
        'rowCount' => 50,
        'searchPhrase' => '',
    ];
}