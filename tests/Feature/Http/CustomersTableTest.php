<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\Port;
use App\Models\User;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('customer graph links use customer name', function () {
    Role::findOrCreate('admin');
    $admin = User::factory()->create(['enabled' => 1])->assignRole('admin');

    $device = Device::factory()->create();
    Port::factory()->for($device)->create([
        'port_descr_type' => 'cust',
        'port_descr_descr' => 'Example Customer',
    ]);

    $response = $this->actingAs($admin)->postJson('/ajax/table/customers', [
        'current' => 1,
        'rowCount' => 50,
        'searchPhrase' => '',
    ]);

    $response->assertOk();
    $content = $response->getContent();
    $this->assertStringContainsString('customer=Example+Customer', $content);
    $this->assertStringNotContainsString('customer_bits/id=', $content);
});