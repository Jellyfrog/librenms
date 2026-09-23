<?php

/**
 * DeviceApiTest.php
 *
 * Tests for the v2 device endpoint
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
 */

namespace LibreNMS\Tests\Feature\Api\V2;

use App\Models\Device;
use App\Models\User;

class DeviceApiTest extends ApiV2TestCase
{
    private const ENDPOINTS = ['/api/v2/devices', '/api/v2/ports', '/api/v2/docs'];

    /**
     * The token is route group middleware, so it is checked once over every
     * v2 endpoint rather than once per resource.
     */
    public function testEndpointsRequireAToken(): void
    {
        foreach (self::ENDPOINTS as $uri) {
            $this->json('GET', $uri)->assertStatus(401);
        }
    }

    public function testListDevices(): void
    {
        $device = Device::factory()->create(['hostname' => 'api-v2-list.example.com']);

        $this->getJsonAs($this->admin(), '/api/v2/devices')
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $device->device_id,
                'hostname' => 'api-v2-list.example.com',
            ]);
    }

    public function testGetDevice(): void
    {
        $device = Device::factory()->create(['hostname' => 'api-v2-item.example.com']);

        $this->getJsonAs($this->admin(), '/api/v2/devices/' . $device->device_id)
            ->assertStatus(200)
            ->assertJson([
                'id' => $device->device_id,
                'hostname' => 'api-v2-item.example.com',
            ]);
    }

    /**
     * The field list is a deny list: Device::$hidden. A column a migration adds
     * is public by default, so this pins the ones that must never be, and
     * testCredentialsCannotBeUsedAsASortKey covers the other way of reading
     * them.
     */
    public function testSnmpCredentialsAreNotExposed(): void
    {
        $credentials = [
            'community' => 'communityvalue',
            'authname' => 'authnamevalue',
            'authpass' => 'authpassvalue',
            'authalgo' => 'SHA-512',
            'authlevel' => 'authPriv',
            'cryptopass' => 'cryptopassvalue',
            'cryptoalgo' => 'AES-256',
        ];

        $device = Device::factory()->create($credentials + ['hostname' => 'api-v2-creds.example.com']);

        $response = $this->getJsonAs($this->admin(), '/api/v2/devices/' . $device->device_id);

        $response->assertStatus(200);
        foreach ($credentials as $field => $value) {
            $response->assertJsonMissingPath($field);
            $this->assertStringNotContainsString($value, $response->getContent());
        }

        $this->assertSame(
            $credentials,
            $device->fresh()->only(array_keys($credentials)),
            'hiding a column must not stop the poller reading it'
        );
    }

    /**
     * order[:property] has an explicit allow-list because the placeholder
     * otherwise offers every column, hidden ones included, which would make
     * the credentials readable one comparison at a time.
     */
    public function testCredentialsCannotBeUsedAsASortKey(): void
    {
        $first = Device::factory()->create(['hostname' => 'api-v2-sort-a.example.com', 'community' => 'zzz']);
        Device::factory()->create(['hostname' => 'api-v2-sort-b.example.com', 'community' => 'aaa']);

        foreach (['order[community]=asc', 'order[authpass]=asc'] as $query) {
            $this->getJsonAs($this->admin(), '/api/v2/devices?' . $query)
                ->assertStatus(200)
                ->assertJsonPath('0.id', $first->device_id);
        }

        $this->getJsonAs($this->admin(), '/api/v2/devices?order[hostname]=desc')
            ->assertStatus(200)
            ->assertJsonPath('0.hostname', 'api-v2-sort-b.example.com');
    }

    /**
     * The camel case columns are the ones api-platform's name conversion
     * breaks, so sysName is covered here alongside the rest.
     */
    public function testDevicesAreFiltered(): void
    {
        $match = Device::factory()->create([
            'hostname' => 'api-v2-filter.example.com',
            'sysName' => 'api-v2-filter',
            'os' => 'linux',
            'type' => 'server',
            'serial' => 'SN-1234',
        ]);
        $other = Device::factory()->create([
            'hostname' => 'api-v2-other.example.com',
            'sysName' => 'api-v2-other',
            'os' => 'ios',
            'type' => 'network',
            'serial' => 'SN-9999',
        ]);

        $filters = [
            'hostname=v2-filter',
            'sysName=api-v2-filter',
            'os=linux',
            'type=server',
            'serial=SN-1234',
        ];

        foreach ($filters as $filter) {
            $this->getJsonAs($this->admin(), '/api/v2/devices?' . $filter)
                ->assertStatus(200)
                ->assertJsonFragment(['id' => $match->device_id])
                ->assertJsonMissing(['id' => $other->device_id]);
        }
    }

    public function testFieldNamesAreNormalized(): void
    {
        $created = Device::factory()->create([
            'hostname' => 'api-v2-fields.example.com',
            'features' => 'ipbase',
            'notes' => 'a note',
        ]);

        $device = $this->getJsonAs($this->admin(), '/api/v2/devices/' . $created->device_id)
            ->assertStatus(200)
            ->json();

        // the device_ prefix is dropped and plurals are singular
        $this->assertArrayHasKey('id', $device);
        $this->assertArrayNotHasKey('device_id', $device);
        $this->assertSame('ipbase', $device['feature'] ?? null);
        $this->assertArrayNotHasKey('features', $device);
        $this->assertSame('a note', $device['note'] ?? null);
        $this->assertArrayNotHasKey('notes', $device);
    }

    public function testUsersOnlySeeTheirOwnDevices(): void
    {
        $permitted = Device::factory()->create(['hostname' => 'api-v2-permitted.example.com']);
        $forbidden = Device::factory()->create(['hostname' => 'api-v2-forbidden.example.com']);

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('user');
        $user->devicesOwned()->attach($permitted->device_id);

        $this->getJsonAs($user, '/api/v2/devices')
            ->assertStatus(200)
            ->assertJsonFragment(['hostname' => 'api-v2-permitted.example.com'])
            ->assertJsonMissing(['hostname' => 'api-v2-forbidden.example.com']);

        $this->getJsonAs($user, '/api/v2/devices/' . $permitted->device_id)->assertStatus(200);
        $this->getJsonAs($user, '/api/v2/devices/' . $forbidden->device_id)->assertStatus(404);
    }

    public function testJsonApiAndJsonLdRepresentations(): void
    {
        Device::factory()->create(['hostname' => 'api-v2-formats.example.com']);

        $this->getJsonAs($this->admin(), '/api/v2/devices', 'application/ld+json')
            ->assertStatus(200)
            ->assertJsonPath('@type', 'Collection')
            ->assertJsonFragment(['hostname' => 'api-v2-formats.example.com']);

        $this->getJsonAs($this->admin(), '/api/v2/devices', 'application/vnd.api+json')
            ->assertStatus(200)
            ->assertJsonPath('data.0.type', 'Device');
    }
}
