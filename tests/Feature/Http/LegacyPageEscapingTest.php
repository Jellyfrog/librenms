<?php

use App\Models\Component;
use App\Models\Device;
use App\Models\Package;
use App\Models\Processor;
use App\Models\User;
use Spatie\Permission\Models\Role;

uses(\LibreNMS\Tests\TestCase::class, \Illuminate\Foundation\Testing\DatabaseTransactions::class);

const LEGACY_PAGE_PAYLOAD = '<img src=x onerror=alert(1)>';
const LEGACY_PAGE_ESCAPED = '&lt;img src=x onerror=alert(1)&gt;';

beforeEach(function (): void {
    Role::findOrCreate('admin');

    // legacy pages emit warnings that production ignores
    set_error_handler(fn (int $severity): bool => ($severity & (E_NOTICE | E_WARNING)) !== 0);
});

afterEach(function (): void {
    restore_error_handler();
});

function legacyPageAdmin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

test('packages pages escape device supplied data', function (): void {
    $device = Device::factory()->create();
    Package::factory()->for($device)->create([
        'name' => LEGACY_PAGE_PAYLOAD,
        'version' => LEGACY_PAGE_PAYLOAD,
    ]);

    $this->actingAs(legacyPageAdmin())
        ->get('/packages')
        ->assertOk()
        ->assertDontSee(LEGACY_PAGE_PAYLOAD, false)
        ->assertSee(LEGACY_PAGE_ESCAPED, false);

    $this->actingAs(legacyPageAdmin())
        ->get(route('device', ['device' => $device, 'tab' => 'packages']))
        ->assertOk()
        ->assertDontSee(LEGACY_PAGE_PAYLOAD, false)
        ->assertSee(LEGACY_PAGE_ESCAPED, false);
});

test('processor page escapes description', function (): void {
    $device = Device::factory()->create();
    Processor::factory()->create([
        'device_id' => $device->device_id,
        'processor_descr' => LEGACY_PAGE_PAYLOAD,
    ]);

    $this->actingAs(legacyPageAdmin())
        ->get(route('device', ['device' => $device, 'tab' => 'health', 'vars' => 'metric=processor']))
        ->assertOk()
        ->assertDontSee(LEGACY_PAGE_PAYLOAD, false)
        ->assertSee(LEGACY_PAGE_ESCAPED, false);
});

test('f5 load balancer pages escape component labels', function (): void {
    $device = Device::factory()->create(['os' => 'f5']);
    $vs = Component::factory()->create([
        'device_id' => $device->device_id,
        'type' => 'f5-ltm-vs',
        'label' => LEGACY_PAGE_PAYLOAD,
        'status' => 0,
        'error' => '',
    ]);

    $this->actingAs(legacyPageAdmin())
        ->get(route('device', ['device' => $device, 'tab' => 'loadbalancer', 'vars' => 'type=ltm_vs']))
        ->assertOk()
        ->assertDontSee(LEGACY_PAGE_PAYLOAD, false)
        ->assertSee(LEGACY_PAGE_ESCAPED, false);

    $this->actingAs(legacyPageAdmin())
        ->get(route('device', ['device' => $device, 'tab' => 'loadbalancer', 'vars' => "type=ltm_vs/subtype=ltm_vs_det/vsid=$vs->id"]))
        ->assertOk()
        ->assertDontSee(LEGACY_PAGE_PAYLOAD, false)
        ->assertSee(LEGACY_PAGE_ESCAPED, false);
});
