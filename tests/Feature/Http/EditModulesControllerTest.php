<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\DevicePollingMethod;
use App\Models\Mempool;
use App\Models\User;
use App\View\Components\Device\EditTabs;
use Illuminate\Support\Facades\Lang;
use Illuminate\Testing\TestResponse;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Enum\ProcessType;
use LibreNMS\Util\ModuleList;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');
});

function admin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

function user(): User
{
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    return $user;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function deviceWithMethods(array $attributes = [], PollingMethodType ...$types): Device
{
    $device = Device::factory()->create($attributes);
    foreach ($types as $type) {
        DevicePollingMethod::factory()->for($device)->create(['method_type' => $type]);
    }

    return $device;
}

/**
 * Module rows passed to the view, keyed by module name
 *
 * @param  TestResponse<\Illuminate\Http\Response>  $response
 * @return array<string, array<string, mixed>>
 */
function modulesFrom(TestResponse $response): array
{
    $modules = $response->viewData('modules');
    expect($modules)->toBeArray();

    return array_column($modules, null, 'module');
}

test('admin can view modules page', function () {
    $device = deviceWithMethods([], PollingMethodType::Snmp);

    $response = $this->actingAs(admin())
        ->get(route('device.edit.modules', $device))
        ->assertOk()
        ->assertSee('Discovery &amp; Polling Modules', false);

    $modules = modulesFrom($response);
    expect($modules)->toHaveKey('mempools');
    $this->assertArrayNotHasKey('core', $modules);
    expect($modules['mempools']['name'])->toBe('Mempools');
});

test('shows modules enabled by polling methods', function () {
    $device = Device::factory()->create();
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::ProxmoxApi,
        'settings' => [],
    ]);

    // the modules tab shows without snmp
    expect($device->polling()->isEnabled(PollingMethodType::Snmp))->toBeFalse();
    $this->actingAs(admin())->get(route('device.edit.polling', $device))
        ->assertSee(route('device.edit.modules', $device), false);

    $modules = modulesFrom($this->get(route('device.edit.modules', $device)));
    expect($modules['vminfo']['discovery']['method'])->toBeTrue();
    expect($modules['vminfo']['polling']['method'])->toBeTrue();
    $this->assertArrayNotHasKey('mempools', $modules, 'mempools does not use the proxmox-api method');
});

test('unix agent method enables the unix agent module', function () {
    LibrenmsConfig::set('poller_modules.unix-agent', false);
    $device = deviceWithMethods([], PollingMethodType::Snmp, PollingMethodType::UnixAgent);

    $modules = modulesFrom($this->actingAs(admin())->get(route('device.edit.modules', $device)));
    expect($modules['unix-agent']['polling']['method'])->toBeTrue();
    expect($modules['mempools']['polling']['method'])->toBeTrue();
    expect($modules['storage']['polling']['method'])->toBeNull('snmp does not enable modules');

    expect((new ModuleList)->moduleStatus(ProcessType::Poller, 'unix-agent', $device)->isEnabled())->toBeTrue();
});

test('ipmi method enables the ipmi module', function () {
    $snmpOnly = deviceWithMethods([], PollingMethodType::Snmp);
    $ipmi = deviceWithMethods([], PollingMethodType::Snmp, PollingMethodType::Ipmi);
    $moduleList = new ModuleList;

    foreach ([ProcessType::Discovery, ProcessType::Poller] as $type) {
        expect($moduleList->moduleStatus($type, 'ipmi', $snmpOnly)->isEnabled())->toBeFalse('ipmi is off by default');
        expect($moduleList->moduleStatus($type, 'ipmi', $ipmi)->isEnabled())->toBeTrue();
    }
});

test('all modules have friendly names', function () {
    foreach (['poller_modules', 'discovery_modules'] as $configKey) {
        foreach (array_keys(LibrenmsConfig::get($configKey)) as $module) {
            if ($module === 'core') {
                continue;
            }

            expect(Lang::has("settings.settings.$configKey.$module.description", 'en'))->toBeTrue("Missing lang/en/settings.php description for $configKey.$module");
        }
    }
});

test('module settings reflect global os and device', function () {
    $device = deviceWithMethods(['os' => 'linux'], PollingMethodType::Snmp);
    LibrenmsConfig::set('poller_modules.mempools', true);
    LibrenmsConfig::set('os.linux.poller_modules.mempools', false);
    LibrenmsConfig::set('discovery_modules.mempools', false);
    $device->setAttrib('poll_mempools', 1);

    $response = $this->actingAs(admin())->get(route('device.edit.modules', $device))->assertOk();

    $mempools = modulesFrom($response)['mempools'];
    expect($mempools['polling'])->toBe(['global' => true, 'os' => false, 'method' => null, 'device' => true]);
    expect($mempools['discovery'])->toBe(['global' => false, 'os' => null, 'method' => null, 'device' => null]);
});

test('module only in one list is not applicable in the other', function () {
    $device = deviceWithMethods([], PollingMethodType::Snmp);
    LibrenmsConfig::set('poller_modules', ['core' => true, 'only-polled' => true]);
    LibrenmsConfig::set('discovery_modules', ['core' => true, 'only-discovered' => false]);

    $response = $this->actingAs(admin())->get(route('device.edit.modules', $device))->assertOk();

    $modules = modulesFrom($response);
    expect(array_keys($modules))->toBe(['only-discovered', 'only-polled']);
    expect($modules['only-polled']['discovery'])->toBeNull();
    expect($modules['only-discovered']['polling'])->toBeNull();
});

test('has data reflects module data', function () {
    $device = deviceWithMethods([], PollingMethodType::Snmp);

    $response = $this->actingAs(admin())->get(route('device.edit.modules', $device));
    expect(modulesFrom($response)['mempools']['has_data'])->toBeFalse();

    Mempool::factory()->for($device)->create();

    $response = $this->actingAs(admin())->get(route('device.edit.modules', $device));
    expect(modulesFrom($response)['mempools']['has_data'])->toBeTrue();
});

test('only modules for enabled methods are shown', function () {
    $device = deviceWithMethods();
    DevicePollingMethod::factory()->for($device)->create(['method_type' => PollingMethodType::UnixAgent, 'affects_availability' => false]);
    DevicePollingMethod::factory()->for($device)->create(['method_type' => PollingMethodType::Snmp, 'enabled' => false]);

    $response = $this->actingAs(admin())->get(route('device.edit.modules', $device))->assertOk();

    $modules = modulesFrom($response);
    expect($modules)->toHaveKey('unix-agent');
    expect($modules)->toHaveKey('mempools');
    $this->assertArrayNotHasKey('availability', $modules);
    // the agent is not checked for availability
    $this->assertArrayNotHasKey('storage', $modules);
    $this->assertArrayNotHasKey('ports', $modules);
    $this->assertArrayNotHasKey('ipmi', $modules);
});

test('availability is shown for methods that affect availability', function () {
    $device = deviceWithMethods([], PollingMethodType::UnixAgent);

    $response = $this->actingAs(admin())->get(route('device.edit.modules', $device))->assertOk();

    expect(modulesFrom($response))->toHaveKey('availability');
});

test('modules with data are shown without their method', function () {
    $device = deviceWithMethods([], PollingMethodType::Icmp);
    Mempool::factory()->for($device)->create();

    $response = $this->actingAs(admin())->get(route('device.edit.modules', $device))->assertOk();

    $modules = modulesFrom($response);
    expect(array_keys($modules))->toBe(['availability', 'mempools']);
    expect($modules['mempools']['has_data'])->toBeTrue();
});

test('icmp only device shows availability module', function () {
    $device = deviceWithMethods([], PollingMethodType::Icmp);

    $response = $this->actingAs(admin())->get(route('device.edit.modules', $device))->assertOk();

    expect(array_keys(modulesFrom($response)))->toBe(['availability']);
});

test('modules page explains when no modules can run', function () {
    $device = deviceWithMethods();
    DevicePollingMethod::factory()->for($device)->create(['method_type' => PollingMethodType::Snmp, 'enabled' => false]);

    $response = $this->actingAs(admin())->get(route('device.edit.modules', $device))
        ->assertOk()
        ->assertSee('No modules can run with the polling methods enabled on this device.');

    expect(modulesFrom($response))->toBe([]);
    expect((new EditTabs($device))->tabs)->toHaveKey('modules');
});

test('user cannot view modules page', function () {
    $device = Device::factory()->create();

    $this->actingAs(user())
        ->get(route('device.edit.modules', $device))
        ->assertForbidden();
});

test('admin can override and clear module', function () {
    $device = Device::factory()->create();
    LibrenmsConfig::set('discovery_modules.mempools', true);

    $this->actingAs(admin())
        ->putJson(route('device.edit.modules.update', [$device, 'mempools']), ['discovery' => 'false'])
        ->assertOk()
        ->assertJson(['discovery' => false]);
    expect($device->fresh()->getAttrib('discover_mempools'))->toBe('0');

    $this->actingAs(admin())
        ->putJson(route('device.edit.modules.update', [$device, 'mempools']), ['discovery' => 'clear'])
        ->assertOk()
        ->assertJson(['discovery' => true]);
    expect($device->fresh()->getAttrib('discover_mempools'))->toBeNull();
});

test('invalid updates are rejected', function () {
    $device = Device::factory()->create();

    $this->actingAs(admin())
        ->putJson(route('device.edit.modules.update', [$device, 'mempools']), ['polling' => 'maybe'])
        ->assertUnprocessable();

    $this->actingAs(admin())
        ->putJson(route('device.edit.modules.update', [$device, 'not-a-module']), ['polling' => 'true'])
        ->assertNotFound();

    $this->actingAs(admin())
        ->putJson(route('device.edit.modules.update', [$device, 'core']), ['polling' => 'false'])
        ->assertNotFound();

    expect($device->fresh()->getAttribs())->toBeEmpty();
});

test('user cannot update module', function () {
    $device = Device::factory()->create();

    $this->actingAs(user())
        ->putJson(route('device.edit.modules.update', [$device, 'mempools']), ['polling' => 'false'])
        ->assertForbidden();

    expect($device->fresh()->getAttrib('poll_mempools'))->toBeNull();
});

test('admin can delete module data', function () {
    $device = Device::factory()->create();
    Mempool::factory()->for($device)->count(2)->create();

    $this->actingAs(admin())
        ->deleteJson(route('device.edit.modules.delete', [$device, 'mempools']))
        ->assertOk()
        ->assertJson(['deleted' => 2]);

    expect($device->mempools()->count())->toBe(0);
});

test('delete rejects unknown module', function () {
    $device = Device::factory()->create();

    $this->actingAs(admin())
        ->deleteJson(route('device.edit.modules.delete', [$device, 'not-a-module']))
        ->assertNotFound();
});

test('user cannot delete module data', function () {
    $device = Device::factory()->create();
    Mempool::factory()->for($device)->create();

    $this->actingAs(user())
        ->deleteJson(route('device.edit.modules.delete', [$device, 'mempools']))
        ->assertForbidden();

    expect($device->mempools()->count())->toBe(1);
});