<?php

namespace LibreNMS\Tests\Feature;

use App\Models\Device;
use App\Models\Ipv4Address;
use App\Models\Ipv6Address;
use App\Models\Port;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LibreNMS\Tests\TestCase;
use LibreNMS\Util\IPv6;
use PHPUnit\Framework\Attributes\DataProvider;

class AddressSearchTest extends TestCase
{
    use RefreshDatabase;

    private Device $permittedDevice;
    private Port $permittedPort;

    protected function setUp(): void
    {
        parent::setUp();

        // a device the user may be granted access to
        $this->permittedDevice = Device::factory()->create();
        $devicePort = Port::factory()->for($this->permittedDevice)->create();
        $this->createAddresses($devicePort, '10.0.1.1', '2001:db8:1::1');

        // a device the user has no access to, one of its ports may be granted directly
        $otherDevice = Device::factory()->create();
        $this->permittedPort = Port::factory()->for($otherDevice)->create();
        $this->createAddresses($this->permittedPort, '10.0.2.1', '2001:db8:2::1');
        $otherPort = Port::factory()->for($otherDevice)->create();
        $this->createAddresses($otherPort, '10.0.3.1', '2001:db8:3::1');
    }

    public static function addressTypes(): array
    {
        return [
            'ipv4' => ['search.ipv4', ['device' => '10.0.1.1/24', 'port' => '10.0.2.1/24', 'other' => '10.0.3.1/24']],
            'ipv6' => ['search.ipv6', ['device' => '2001:db8:1::1/64', 'port' => '2001:db8:2::1/64', 'other' => '2001:db8:3::1/64']],
        ];
    }

    #[DataProvider('addressTypes')]
    public function test_user_with_device_permission_sees_addresses_on_that_device(string $route, array $addresses): void
    {
        $user = $this->restrictedUser();
        $user->devicesOwned()->attach($this->permittedDevice->device_id);

        $this->assertSearchReturns($user, $route, [$addresses['device']]);
    }

    #[DataProvider('addressTypes')]
    public function test_user_with_port_permission_sees_addresses_on_that_port(string $route, array $addresses): void
    {
        $user = $this->restrictedUser();
        $user->portsOwned()->attach($this->permittedPort->port_id);

        $this->assertSearchReturns($user, $route, [$addresses['port']]);
    }

    #[DataProvider('addressTypes')]
    public function test_user_with_device_and_port_permissions_sees_addresses_on_both(string $route, array $addresses): void
    {
        $user = $this->restrictedUser();
        $user->devicesOwned()->attach($this->permittedDevice->device_id);
        $user->portsOwned()->attach($this->permittedPort->port_id);

        $this->assertSearchReturns($user, $route, [$addresses['device'], $addresses['port']]);
    }

    #[DataProvider('addressTypes')]
    public function test_user_without_permissions_sees_no_addresses(string $route, array $addresses): void
    {
        $this->assertSearchReturns($this->restrictedUser(), $route, []);
    }

    #[DataProvider('addressTypes')]
    public function test_admin_sees_all_addresses(string $route, array $addresses): void
    {
        $admin = User::factory()->admin()->create(['enabled' => 1]);

        $this->assertSearchReturns($admin, $route, array_values($addresses));
    }

    private function restrictedUser(): User
    {
        $user = User::factory()->create(['enabled' => 1]);
        $user->assignRole('user');

        return $user;
    }

    private function createAddresses(Port $port, string $ipv4, string $ipv6): void
    {
        Ipv4Address::factory()->create([
            'port_id' => $port->port_id,
            'ipv4_address' => $ipv4,
            'ipv4_prefixlen' => 24,
        ]);

        $ip = new IPv6($ipv6);
        Ipv6Address::factory()->create([
            'port_id' => $port->port_id,
            'ipv6_address' => $ip->uncompressed(),
            'ipv6_compressed' => $ip->compressed(),
            'ipv6_prefixlen' => 64,
        ]);
    }

    private function assertSearchReturns(User $user, string $route, array $expected): void
    {
        // same request the search page sends by default
        $response = $this->actingAs($user)
            ->postJson(route($route), ['current' => 1, 'rowCount' => 50, 'sort' => ['hostname' => 'asc']]);

        $response->assertOk()
            ->assertJsonPath('total', count($expected));

        $this->assertEqualsCanonicalizing($expected, array_column($response->json('rows'), 'address'));
    }
}
