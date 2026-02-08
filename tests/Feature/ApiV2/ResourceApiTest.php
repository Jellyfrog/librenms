<?php

namespace LibreNMS\Tests\Feature\ApiV2;

use App\Models\ApiToken;
use App\Models\Device;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use LibreNMS\Tests\DBTestCase;

class ResourceApiTest extends DBTestCase
{
    use DatabaseTransactions;

    private function authHeaders(User $user): array
    {
        $token = ApiToken::generateToken($user);

        return ['X-Auth-Token' => $token->token_hash];
    }

    // --- Location endpoints ---

    public function testListLocations(): void
    {
        $user = User::factory()->admin()->create();
        Location::factory()->create(['location' => 'Data Center 1']);

        $response = $this->getJson('/api/v2/locations', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    public function testCreateLocation(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->postJson('/api/v2/locations', [
            'location' => 'New Data Center via API v2',
            'lat' => 37.7749,
            'lng' => -122.4194,
        ], $this->authHeaders($user));

        $response->assertStatus(201);
    }

    public function testUpdateLocation(): void
    {
        $user = User::factory()->admin()->create();
        $location = Location::factory()->create(['location' => 'Old Name']);

        $response = $this->patchJson(
            "/api/v2/locations/{$location->id}",
            ['location' => 'Updated Name'],
            $this->authHeaders($user)
        );

        $response->assertStatus(200);
    }

    public function testDeleteLocation(): void
    {
        $user = User::factory()->admin()->create();
        $location = Location::factory()->create();

        $response = $this->deleteJson(
            "/api/v2/locations/{$location->id}",
            [],
            $this->authHeaders($user)
        );

        $response->assertStatus(204);
    }

    // --- Sensor endpoints ---

    public function testListSensors(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/sensors', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- Eventlog endpoints ---

    public function testListEventlogs(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/eventlogs', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- Syslog endpoints ---

    public function testListSyslogs(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/syslogs', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- BGP endpoints ---

    public function testListBgpPeers(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/bgp_peers', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- OSPF endpoints ---

    public function testListOspfInstances(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/ospf_instances', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    public function testListOspfNbrs(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/ospf_nbrs', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    public function testListOspfPorts(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/ospf_ports', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- VLAN endpoints ---

    public function testListVlans(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/vlans', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- VRF endpoints ---

    public function testListVrfs(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/vrfs', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- Health endpoints ---

    public function testListMempools(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/mempools', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    public function testListProcessors(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/processors', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    public function testListStorage(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/storages', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- Network endpoints ---

    public function testListIpv4Addresses(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/ipv4_addresses', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    public function testListArpEntries(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/arp_entries', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    public function testListFdbEntries(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/fdb_entries', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- Link endpoints ---

    public function testListLinks(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/links', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- Service endpoints ---

    public function testListServices(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/services', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- Bill endpoints ---

    public function testListBills(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/bills', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- Device Group endpoints ---

    public function testListDeviceGroups(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/device_groups', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- Port Group endpoints ---

    public function testListPortGroups(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/port_groups', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- Poller endpoints ---

    public function testListPollers(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/pollers', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- Component endpoints ---

    public function testListComponents(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/components', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- Wireless sensor endpoints ---

    public function testListWirelessSensors(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/wireless_sensors', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- Port security endpoints ---

    public function testListPortSecurity(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/port_securities', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- Availability endpoints ---

    public function testListAvailability(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/availabilities', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- MPLS endpoints ---

    public function testListMplsServices(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/mpls_services', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- NAC endpoints ---

    public function testListNacEntries(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/nac_entries', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    // --- IPsec endpoints ---

    public function testListIpsecTunnels(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/ipsec_tunnels', $this->authHeaders($user));

        $response->assertStatus(200);
    }
}
