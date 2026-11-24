<?php

namespace LibreNMS\Tests\Feature\Http;

use App\Models\Device;
use App\Models\Port;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LibreNMS\Tests\TestCase;
use Spatie\Permission\Models\Role;

final class CustomersTableTest extends TestCase
{
    use RefreshDatabase;

    public function testCustomerGraphLinksUseCustomerName(): void
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create(['enabled' => 1])->assignRole('admin');

        $device = Device::factory()->create();
        Port::factory()->for($device)->create([
            'port_descr_type' => 'cust',
            'port_descr_descr' => 'Example Customer',
        ]);

        Port::factory()->for($device)->create([
            'port_descr_type' => 'cust',
            'port_descr_descr' => 'Example Customer',
        ]);
        Port::factory()->for($device)->create([
            'port_descr_type' => 'cust',
            'port_descr_descr' => 'Other Customer',
        ]);

        $response = $this->actingAs($admin)->getJson(route('table.customers', ['per_page' => 50]));

        // one row per customer with its ports
        $response->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.port_descr_descr', 'Example Customer')
            ->assertJsonCount(2, 'data.0.ports')
            ->assertJsonCount(1, 'data.1.ports');
        $this->actingAs($admin)->getJson(route('table.customers', ['search' => 'other']))->assertJsonPath('meta.total', 1);
        $content = json_encode($response->json('data.0.graphs'), JSON_UNESCAPED_SLASHES);
        $this->assertStringContainsString('customer=Example+Customer', $content);
        $this->assertStringNotContainsString('customer_bits/id=', $content);
    }
}
