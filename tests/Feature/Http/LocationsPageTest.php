<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\Location;
use App\Models\User;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');
});

test('page renders table with data url', function () {
    $this->actingAs(admin())
        ->get('/locations')
        ->assertOk()
        ->assertSee('id="locations"', false)
        ->assertSee('data-url="' . route('table.location') . '"', false);
});

test('table only returns locations of accessible devices', function () {
    $visible = Location::factory()->create(['location' => 'Visible']);
    $hidden = Location::factory()->create(['location' => 'Hidden']);
    $device = Device::factory()->create(['location_id' => $visible->id]);
    Device::factory()->create(['location_id' => $hidden->id]);

    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');
    $user->devicesOwned()->attach($device);

    $response = $this->actingAs($user)->postJson(route('table.location'), tableRequest());

    $response->assertOk()->assertJsonPath('total', 1);
    $this->assertStringNotContainsString('Hidden', $response->getContent());
});

test('table returns raw location names', function () {
    $name = '"><script>alert(1)</script>';
    Location::factory()->create(['location' => $name]);

    // escaping is done by the page when rendering, see LocationsPageBrowserTest
    $this->actingAs(admin())
        ->postJson(route('table.location'), tableRequest())
        ->assertOk()
        ->assertJsonPath('rows.0.location', $name);
});

test('export keeps raw location names', function () {
    Location::factory()->create(['location' => 'R&D "Lab"']);

    $csv = $this->actingAs(admin())->get('/ajax/table/location/export')->streamedContent();

    $this->assertStringContainsString('R&D ""Lab""', $csv);
    $this->assertStringNotContainsString('&amp;', $csv);
});

test('export returns csv with headers and rows', function () {
    $location = Location::factory()->create(['location' => 'Berlin', 'lat' => 52.5, 'lng' => 13.4]);
    Device::factory()->create(['location_id' => $location->id]);

    $response = $this->actingAs(admin())->get('/ajax/table/location/export');

    $response->assertOk();
    $csv = preg_replace('/^\xEF\xBB\xBF/', '', $response->streamedContent());
    $lines = array_values(array_filter(explode("\n", trim($csv))));

    expect($lines)->toHaveCount(2);
    expect(str_getcsv($lines[0], escape: '\\'))->toBe(['ID', 'Location', 'Latitude', 'Longitude', 'Devices', 'Down']);
    expect(str_getcsv($lines[1], escape: '\\')[1])->toBe('Berlin');
    expect(str_getcsv($lines[1], escape: '\\')[4])->toBe('1');
});

function admin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

/** @return array<string, mixed> */
function tableRequest(): array
{
    return [
        'current' => 1,
        'rowCount' => 50,
        'searchPhrase' => '',
    ];
}