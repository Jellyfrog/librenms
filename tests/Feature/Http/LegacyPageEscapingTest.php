<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Component;
use App\Models\Device;
use App\Models\Package;
use App\Models\Processor;
use App\Models\User;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

private const string PAYLOAD = '<img src=x onerror=alert(1)>';
private const string ESCAPED = '&lt;img src=x onerror=alert(1)&gt;';

beforeEach(function () {
    Role::findOrCreate('admin');

    // legacy pages emit warnings that production ignores
    set_error_handler(fn (int $severity): bool => ($severity & (E_NOTICE | E_WARNING)) !== 0);
});

afterEach(function () {
    restore_error_handler();

});

function admin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

test('packages pages escape device supplied data', function () {
    $device = Device::factory()->create();
    Package::factory()->for($device)->create([
        'name' => self::PAYLOAD,
        'version' => self::PAYLOAD,
    ]);

    $this->actingAs(admin())
        ->get('/packages')
        ->assertOk()
        ->assertDontSee(self::PAYLOAD, false)
        ->assertSee(self::ESCAPED, false);

    $this->actingAs(admin())
        ->get(route('device', ['device' => $device, 'tab' => 'packages']))
        ->assertOk()
        ->assertDontSee(self::PAYLOAD, false)
        ->assertSee(self::ESCAPED, false);
});

test('processor page escapes description', function () {
    $device = Device::factory()->create();
    Processor::factory()->create([
        'device_id' => $device->device_id,
        'processor_descr' => self::PAYLOAD,
    ]);

    $this->actingAs(admin())
        ->get(route('device', ['device' => $device, 'tab' => 'health', 'vars' => 'metric=processor']))
        ->assertOk()
        ->assertDontSee(self::PAYLOAD, false)
        ->assertSee(self::ESCAPED, false);
});

test('f5 load balancer pages escape component labels', function () {
    $device = Device::factory()->create(['os' => 'f5']);
    $vs = Component::factory()->create([
        'device_id' => $device->device_id,
        'type' => 'f5-ltm-vs',
        'label' => self::PAYLOAD,
        'status' => 0,
        'error' => '',
    ]);

    $this->actingAs(admin())
        ->get(route('device', ['device' => $device, 'tab' => 'loadbalancer', 'vars' => 'type=ltm_vs']))
        ->assertOk()
        ->assertDontSee(self::PAYLOAD, false)
        ->assertSee(self::ESCAPED, false);

    $this->actingAs(admin())
        ->get(route('device', ['device' => $device, 'tab' => 'loadbalancer', 'vars' => "type=ltm_vs/subtype=ltm_vs_det/vsid=$vs->id"]))
        ->assertOk()
        ->assertDontSee(self::PAYLOAD, false)
        ->assertSee(self::ESCAPED, false);
});