<?php

/**
 * DeviceApiTest.php
 *
 * -Description-
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

use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\User;

class DeviceApiTest extends ApiV2TestCase
{
    public function testDevicesRequireAToken(): void
    {
        $this->json('GET', '/api/v2/devices')->assertStatus(401);
    }

    public function testDevicesAreHiddenWhenV2IsDisabled(): void
    {
        LibrenmsConfig::set('api.v2.enabled', false);

        $this->getJsonAs($this->admin(), '/api/v2/devices')->assertStatus(404);
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

    public function testSnmpCredentialsAreNotExposed(): void
    {
        $credentials = [
            'community' => 'communityvalue',
            'authname' => 'authnamevalue',
            'authpass' => 'authpassvalue',
            'cryptopass' => 'cryptopassvalue',
        ];

        $device = Device::factory()->create($credentials + ['hostname' => 'api-v2-creds.example.com']);

        $response = $this->getJsonAs($this->admin(), '/api/v2/devices/' . $device->device_id);

        $response->assertStatus(200);
        foreach (array_keys($credentials) as $field) {
            $response->assertJsonMissingPath($field);
        }
        foreach ($credentials as $value) {
            $this->assertStringNotContainsString($value, $response->getContent());
        }
    }

    public function testDevicesAreFiltered(): void
    {
        Device::factory()->create(['hostname' => 'api-v2-filter.example.com', 'os' => 'linux']);
        Device::factory()->create(['hostname' => 'api-v2-other.example.com', 'os' => 'ios']);

        $admin = $this->admin();

        $this->getJsonAs($admin, '/api/v2/devices?os=linux')
            ->assertStatus(200)
            ->assertJsonFragment(['hostname' => 'api-v2-filter.example.com'])
            ->assertJsonMissing(['hostname' => 'api-v2-other.example.com']);

        $this->getJsonAs($admin, '/api/v2/devices?hostname=v2-other')
            ->assertStatus(200)
            ->assertJsonFragment(['hostname' => 'api-v2-other.example.com'])
            ->assertJsonMissing(['hostname' => 'api-v2-filter.example.com']);
    }

    public function testFieldNamesAreNormalized(): void
    {
        Device::factory()->create([
            'hostname' => 'api-v2-fields.example.com',
            'features' => 'ipbase',
            'notes' => 'a note',
        ]);

        $device = $this->getJsonAs($this->admin(), '/api/v2/devices?hostname=api-v2-fields')
            ->assertStatus(200)
            ->json('0');

        // the device_ prefix is dropped and plurals are singular
        $this->assertArrayHasKey('id', $device);
        $this->assertArrayNotHasKey('device_id', $device);
        $this->assertSame('ipbase', $device['feature'] ?? null);
        $this->assertArrayNotHasKey('features', $device);
        $this->assertSame('a note', $device['note'] ?? null);
        $this->assertArrayNotHasKey('notes', $device);
    }

    /**
     * sysName is a camel case column, the ones api-platform's name conversion
     * breaks, so it gets its own check.
     */
    public function testDevicesAreFilteredBySysName(): void
    {
        Device::factory()->create(['hostname' => 'api-v2-sysname.example.com', 'sysName' => 'api-v2-sysname']);
        Device::factory()->create(['hostname' => 'api-v2-nosysname.example.com', 'sysName' => 'something-else']);

        $this->getJsonAs($this->admin(), '/api/v2/devices?sysName=api-v2-sysname')
            ->assertStatus(200)
            ->assertJsonFragment(['hostname' => 'api-v2-sysname.example.com'])
            ->assertJsonMissing(['hostname' => 'api-v2-nosysname.example.com']);
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
        $admin = $this->admin();

        $this->getJsonAs($admin, '/api/v2/devices', 'application/ld+json')
            ->assertStatus(200)
            ->assertJsonPath('@type', 'Collection')
            ->assertJsonFragment(['hostname' => 'api-v2-formats.example.com']);

        $this->getJsonAs($admin, '/api/v2/devices', 'application/vnd.api+json')
            ->assertStatus(200)
            ->assertJsonPath('data.0.type', 'Device');
    }
}
