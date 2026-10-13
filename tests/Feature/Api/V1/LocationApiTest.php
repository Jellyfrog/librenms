<?php

namespace LibreNMS\Tests\Feature\Api\V1;

use App\Models\Device;
use App\Models\Location;

final class LocationApiTest extends ApiV1TestCase
{
    public function testAdminListsLocationsWithApiFieldNames(): void
    {
        $location = Location::factory()->withCoordinates()->create(['location' => 'Rack 4, Stockholm']);

        $response = $this->getApi('/api/v1/locations', $this->admin())->assertOk();

        $data = $response->collect()->firstWhere('id', $location->id);
        $this->assertNotNull($data, 'The created location is in the list');
        $this->assertSame('Rack 4, Stockholm', $data['name']);
        $this->assertEqualsWithDelta($location->lat, $data['lat'], 0.0001);
        $this->assertEqualsWithDelta($location->lng, $data['lng'], 0.0001);
        $this->assertFalse($data['fixed_coordinates']);

        // the API name, not the column name
        $this->assertArrayNotHasKey('location', $data);
    }

    public function testShowsSingleLocation(): void
    {
        $location = Location::factory()->create(['location' => 'Rack 4, Stockholm']);
        $admin = $this->admin();

        $this->getApi("/api/v1/locations/$location->id", $admin)
            ->assertOk()
            ->assertJsonPath('id', $location->id)
            ->assertJsonPath('name', 'Rack 4, Stockholm');

        $this->getApi('/api/v1/locations/999999999', $admin)->assertNotFound();
    }

    public function testLimitedUserOnlySeesLocationsOfTheirDevices(): void
    {
        $mine = Location::factory()->create();
        $other = Location::factory()->create();
        $device = Device::factory()->create(['location_id' => $mine->id]);
        Device::factory()->create(['location_id' => $other->id]);
        $user = $this->limitedUser($device);

        $ids = $this->getApi('/api/v1/locations', $user)->assertOk()->collect()->pluck('id')->all();

        $this->assertSame([$mine->id], $ids);

        $this->getApi("/api/v1/locations/$mine->id", $user)->assertOk();
        $this->getApi("/api/v1/locations/$other->id", $user)->assertNotFound();
    }

    public function testFiltersAndOrdersByApiFieldName(): void
    {
        Location::factory()->create(['location' => 'Stockholm DC2']);
        Location::factory()->create(['location' => 'Stockholm DC1']);
        Location::factory()->create(['location' => 'Gothenburg']);

        $names = $this->getApi('/api/v1/locations?name=Stockholm&order[name]=asc', $this->admin())
            ->assertOk()
            ->collect()
            ->pluck('name')
            ->all();

        $this->assertSame(['Stockholm DC1', 'Stockholm DC2'], $names);
    }

    public function testDeviceRefersToItsLocationById(): void
    {
        $location = Location::factory()->create();
        $device = Device::factory()->create(['location_id' => $location->id]);
        $admin = $this->admin();

        $this->getApi("/api/v1/devices/$device->device_id", $admin)
            ->assertOk()
            ->assertJsonPath('location_id', $location->id)
            ->assertJsonMissingPath('location');
    }

    public function testListsDevicesAtLocation(): void
    {
        $location = Location::factory()->create();
        $here = Device::factory()->create(['location_id' => $location->id]);
        Device::factory()->create(['location_id' => Location::factory()->create()->id]);
        $admin = $this->admin();

        $ids = $this->getApi("/api/v1/locations/$location->id/devices", $admin)->assertOk()->collect()->pluck('id')->all();

        $this->assertSame([$here->device_id], $ids);

        $this->getApi('/api/v1/locations/999999999/devices', $admin)->assertOk()->assertExactJson([]);
    }

    public function testDevicesAtLocationAreLimitedToTheUser(): void
    {
        $location = Location::factory()->create();
        $mine = Device::factory()->create(['location_id' => $location->id]);
        Device::factory()->create(['location_id' => $location->id]);
        $hidden = Location::factory()->create();
        Device::factory()->create(['location_id' => $hidden->id]);
        $user = $this->limitedUser($mine);

        $ids = $this->getApi("/api/v1/locations/$location->id/devices", $user)->assertOk()->collect()->pluck('id')->all();

        $this->assertSame([$mine->device_id], $ids);

        // nothing to see at a location with none of the user's devices
        $this->getApi("/api/v1/locations/$hidden->id/devices", $user)->assertOk()->assertExactJson([]);
    }

    public function testResourcesHaveNoRelationsToInclude(): void
    {
        $location = Location::factory()->create(['location' => 'Rack 4, Stockholm']);
        $mine = Device::factory()->create(['location_id' => $location->id]);
        $other = Device::factory()->create(['location_id' => $location->id, 'sysName' => 'not-for-this-user']);
        $headers = $this->headers($this->limitedUser($mine)) + ['Accept' => 'application/vnd.api+json'];

        $uris = [
            "/api/v1/locations/$location->id?include=devices",
            "/api/v1/devices/$mine->device_id?include=location",
            '/api/v1/devices?include=location.devices',
        ];

        foreach ($uris as $uri) {
            auth()->forgetGuards();
            $this->get($uri, $headers)
                ->assertOk()
                ->assertJsonMissingPath('included')
                ->assertJsonMissingPath('data.relationships')
                ->assertJsonMissingPath('data.0.relationships')
                ->assertDontSee('"id":"' . $other->device_id . '"', false)
                ->assertDontSee('not-for-this-user');
        }
    }

    public function testRequiresAuthentication(): void
    {
        $this->json('GET', '/api/v1/locations')->assertUnauthorized();
    }
}
