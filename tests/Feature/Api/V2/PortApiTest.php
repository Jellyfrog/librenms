<?php

/**
 * PortApiTest.php
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
use App\Models\Port;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use LibreNMS\Tests\DBTestCase;

class PortApiTest extends DBTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        LibrenmsConfig::set('api.v2.enabled', true);
    }

    public function testPortsRequireAToken(): void
    {
        $this->json('GET', '/api/v2/ports')->assertStatus(401);
    }

    public function testPortsAreHiddenWhenV2IsDisabled(): void
    {
        LibrenmsConfig::set('api.v2.enabled', false);

        $this->getJsonAs($this->admin(), '/api/v2/ports')->assertStatus(404);
    }

    public function testListPorts(): void
    {
        $port = $this->port(['ifName' => 'api-v2-list']);

        $this->getJsonAs($this->admin(), '/api/v2/ports?ifName=api-v2-list')
            ->assertStatus(200)
            ->assertJsonFragment([
                'port_id' => $port->port_id,
                'device_id' => $port->device_id,
                'ifName' => 'api-v2-list',
            ]);
    }

    public function testGetPort(): void
    {
        $port = $this->port(['ifName' => 'api-v2-item', 'ifAlias' => 'uplink to core']);

        $this->getJsonAs($this->admin(), '/api/v2/ports/' . $port->port_id)
            ->assertStatus(200)
            ->assertJson([
                'port_id' => $port->port_id,
                'ifName' => 'api-v2-item',
                'ifAlias' => 'uplink to core',
            ]);
    }

    /**
     * The camel case columns are the ones api-platform's name conversion breaks,
     * so every one of them that is offered as a filter is checked here.
     */
    public function testPortsAreFiltered(): void
    {
        $device = Device::factory()->create();
        $match = $this->port([
            'device_id' => $device->device_id,
            'ifName' => 'api-v2-match',
            'ifAlias' => 'uplink to core',
            'portName' => 'Gi0/1',
            'ifType' => 'ethernetCsmacd',
            'ifOperStatus' => 'up',
            'ifAdminStatus' => 'up',
            'ifVlan' => '10',
        ]);
        $other = $this->port([
            'device_id' => $device->device_id,
            'ifName' => 'api-v2-other',
            'ifAlias' => 'unused',
            'portName' => 'Gi0/2',
            'ifType' => 'softwareLoopback',
            'ifOperStatus' => 'down',
            'ifAdminStatus' => 'down',
            'ifVlan' => '20',
        ]);

        $admin = $this->admin();

        $filters = [
            'ifName=api-v2-match',
            'ifAlias=uplink',
            'portName=Gi0/1',
            'ifType=ethernetCsmacd',
            'ifOperStatus=up',
            'ifAdminStatus=up',
            'ifVlan=10',
            'ifIndex=' . $match->ifIndex,
        ];

        foreach ($filters as $filter) {
            $this->getJsonAs($admin, '/api/v2/ports?' . $filter)
                ->assertStatus(200)
                ->assertJsonFragment(['port_id' => $match->port_id])
                ->assertJsonMissing(['port_id' => $other->port_id]);
        }

        $this->getJsonAs($admin, '/api/v2/ports?device_id=' . $device->device_id)
            ->assertStatus(200)
            ->assertJsonFragment(['port_id' => $match->port_id])
            ->assertJsonFragment(['port_id' => $other->port_id]);
    }

    public function testPortsAreOrdered(): void
    {
        $device = Device::factory()->create();
        $this->port(['device_id' => $device->device_id, 'ifName' => 'bbb']);
        $this->port(['device_id' => $device->device_id, 'ifName' => 'aaa']);

        $response = $this->getJsonAs(
            $this->admin(),
            '/api/v2/devices/' . $device->device_id . '/ports?' . urlencode('order[ifName]') . '=desc'
        );

        $response->assertStatus(200);
        $this->assertSame(['bbb', 'aaa'], array_column($response->json(), 'ifName'));
    }

    public function testDevicePortsSubresource(): void
    {
        $device = Device::factory()->create();
        $mine = $this->port(['device_id' => $device->device_id]);
        $theirs = $this->port();

        $this->getJsonAs($this->admin(), '/api/v2/devices/' . $device->device_id . '/ports')
            ->assertStatus(200)
            ->assertJsonFragment(['port_id' => $mine->port_id])
            ->assertJsonMissing(['port_id' => $theirs->port_id]);
    }

    public function testUsersOnlySeeThePortsTheyMayAccess(): void
    {
        $device = Device::factory()->create();
        $viaDevice = $this->port(['device_id' => $device->device_id]);
        $viaPort = $this->port();
        $forbidden = $this->port();

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('user');
        $user->devicesOwned()->attach($device->device_id);
        $user->portsOwned()->attach($viaPort->port_id);

        $this->getJsonAs($user, '/api/v2/ports')
            ->assertStatus(200)
            ->assertJsonFragment(['port_id' => $viaDevice->port_id])
            ->assertJsonFragment(['port_id' => $viaPort->port_id])
            ->assertJsonMissing(['port_id' => $forbidden->port_id]);

        $this->getJsonAs($user, '/api/v2/ports/' . $viaDevice->port_id)->assertStatus(200);
        $this->getJsonAs($user, '/api/v2/ports/' . $viaPort->port_id)->assertStatus(200);
        $this->getJsonAs($user, '/api/v2/ports/' . $forbidden->port_id)->assertStatus(404);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function port(array $attributes = []): Port
    {
        return Port::factory()->create($attributes + ['device_id' => Device::factory()->create()->device_id]);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function getJsonAs(User $user, string $uri): TestResponse
    {
        auth()->forgetGuards();

        return $this->json('GET', $uri, [], [
            'Authorization' => 'Bearer ' . $user->createToken('test')->plainTextToken,
        ]);
    }
}
