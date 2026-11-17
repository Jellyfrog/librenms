<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\DevicePollingMethod;
use App\Models\Mempool;
use App\Models\Storage;
use App\Models\User;
use App\View\Components\Device\EditTabs;
use LibreNMS\Enum\PollingMethodType;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function editTabsDeviceWithMethods(PollingMethodType ...$types): Device
{
    $device = Device::factory()->create();
    foreach ($types as $type) {
        DevicePollingMethod::factory()->for($device)->create(['method_type' => $type]);
    }

    return $device;
}

test('data tabs are shown when the device has that data', function () {
    $device = editTabsDeviceWithMethods(PollingMethodType::UnixAgent);
    Mempool::factory()->for($device)->create();
    Storage::factory()->for($device)->create();

    $tabs = (new EditTabs($device))->tabs;

    expect($tabs)->toHaveKey('mempools');
    expect($tabs)->toHaveKey('storage');
    $this->assertArrayNotHasKey('processors', $tabs);
    $this->assertArrayNotHasKey('ports', $tabs);
});

test('applications tab needs snmp', function () {
    expect((new EditTabs(editTabsDeviceWithMethods(PollingMethodType::Snmp)))->tabs)->toHaveKey('apps');
    $this->assertArrayNotHasKey('apps', (new EditTabs(editTabsDeviceWithMethods(PollingMethodType::UnixAgent)))->tabs);
});

test('modules tab is always shown', function () {
    $tabs = (new EditTabs(editTabsDeviceWithMethods()))->tabs;

    expect($tabs)->toHaveKey('modules');
    foreach (['ports', 'apps', 'storage', 'processors', 'mempools'] as $tab) {
        $this->assertArrayNotHasKey($tab, $tabs);
    }
});

test('legacy edit page uses tabs', function () {
    Role::findOrCreate('admin');
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');
    $device = editTabsDeviceWithMethods(PollingMethodType::UnixAgent);
    Mempool::factory()->for($device)->create();

    $response = $this->actingAs($admin)
        ->get("/device/device=$device->device_id/tab=edit/section=alert-rules/")
        ->assertOk()
        ->assertSee(route('device.edit.mempools', $device), false)
        ->assertDontSee(route('device.edit.storage', $device), false);

    expect((string) $response->getContent())->toMatch('#<span class="pagemenu-selected">\s*<a href="[^"]*section=alert-rules"#');
});