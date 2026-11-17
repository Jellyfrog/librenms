<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Models\Device;
use App\Models\DevicePollingMethod;
use App\Models\Secret;
use App\Models\User;
use LibreNMS\Enum\AddressFamily;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Enum\SecretType;
use LibreNMS\Polling\Method\Config\UnixAgentConfig;
use LibreNMS\Polling\Method\Methods\IcmpPollingMethod;
use LibreNMS\Polling\Method\PollingMethodRegistry;
use LibreNMS\Polling\Secrets\Definitions\SecretDefinition;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    Role::findOrCreate('admin');
    Permission::findOrCreate('device.update');
    Permission::findOrCreate('secret.create');
    Permission::findOrCreate('secret.update');
    Permission::findOrCreate('secret.view');
    Permission::findOrCreate('secret.delete');
    Permission::findOrCreate('secret.unmask');
});

test('update snmp polling method updates port association mode', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $device = Device::factory()->create();

    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Snmp,
        'enabled' => true,
        'settings' => [
            'transport' => 'udp',
            'port' => 161,
            'timeout' => 1,
            'retries' => 0,
            'max_repeaters' => 0,
            'max_oid' => 10,
            'port_association_mode' => 'ifIndex',
        ],
    ]);

    $response = $this->actingAs($admin)->put(
        route('device.edit.polling.update', ['device' => $device, 'methodType' => 'snmp']),
        [
            'enabled' => '1',
            'affects_availability' => '1',
            'force_save' => '1',
            'settings' => [
                'transport' => 'udp',
                'port' => 161,
                'timeout' => 1,
                'retries' => 0,
                'max_repeaters' => 0,
                'max_oid' => 10,
                'port_association_mode' => 'ifName',
            ],
        ]
    );

    $response->assertRedirect();
    expect($device->fresh()->pollingMethod(PollingMethodType::Snmp)->settings['port_association_mode'])->toEqual('ifName');
    expect($device->fresh()->polling()->snmp()->portAssociationMode)->toEqual('ifName');
});

test('store polling method rejects duplicate default secret description', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $device = Device::factory()->create(['hostname' => 'test-device.example.com']);

    // Pre-create a secret that has the exact default description
    Secret::create([
        'description' => 'SNMP test-device.example.com',
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => 'public'],
    ]);

    $response = $this->actingAs($admin)->post(
        route('device.edit.polling.store', ['device' => $device]),
        [
            'method_type' => 'snmp',
            'secret_mode' => 'new',
            'description' => 'SNMP test-device.example.com',
            'secret_type' => 'snmp-community',
            'secret_data' => [
                'community' => 'private',
            ],
            'settings' => [
                'transport' => 'udp',
                'port' => 161,
                'timeout' => 1,
                'retries' => 0,
                'max_repeaters' => 0,
                'max_oid' => 10,
                'port_association_mode' => 'ifIndex',
            ],
        ]
    );

    $response->assertSessionHasErrors(['description']);
});

test('store polling method rejects duplicate custom secret description', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $device = Device::factory()->create(['hostname' => 'test-device2.example.com']);

    Secret::create([
        'description' => 'Existing Custom Description',
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => 'public'],
    ]);

    $response = $this->actingAs($admin)->post(
        route('device.edit.polling.store', ['device' => $device]),
        [
            'method_type' => 'snmp',
            'secret_mode' => 'new',
            'description' => 'Existing Custom Description',
            'secret_type' => 'snmp-community',
            'secret_data' => [
                'community' => 'private',
            ],
            'settings' => [
                'transport' => 'udp',
                'port' => 161,
                'timeout' => 1,
                'retries' => 0,
                'max_repeaters' => 0,
                'max_oid' => 10,
                'port_association_mode' => 'ifIndex',
            ],
        ]
    );

    $response->assertSessionHasErrors(['description']);
});

test('index renders polling view without secret data', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $secret = Secret::create([
        'description' => 'SNMP Secret 123',
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => 'public'],
    ]);

    $device = Device::factory()->create(['hostname' => 'test-device.example.com']);

    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Snmp,
        'secret_id' => $secret->id,
        'enabled' => true,
    ]);

    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Icmp,
        'enabled' => true,
    ]);

    $response = $this->actingAs($admin)->get(route('device.edit.polling', ['device' => $device]));
    $response->assertOk();
    $response->assertSee('SNMP Secret 123');
    $response->assertViewHas('allMethods', function ($allMethods) use ($secret) {
        $snmp = $allMethods->firstWhere('type', 'snmp');
        expect($snmp['secret'])->toBe(['id' => $secret->id, 'description' => 'SNMP Secret 123', 'secret_type' => 'snmp-community']);

        return true;
    });
    $response->assertDontSee('"community"', false);
    // secret data is loaded on demand
});

test('index renders each secret option and id once', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);
    $secret = Secret::create([
        'description' => 'SNMP Secret 456',
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => 'public'],
    ]);
    $device = Device::factory()->create();
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Snmp,
        'secret_id' => $secret->id,
    ]);

    $html = $this->actingAs($admin)->get(route('device.edit.polling', ['device' => $device]))->assertOk()->getContent();

    // the edit and new secret forms are both on the page
    preg_match_all('/\sid="([^"]+)"/', $html, $ids);
    expect(array_keys(array_filter(array_count_values($ids[1]), fn (int $count): bool => $count > 1)))->toBe([], 'duplicate element ids');

    // the selected secret is rendered as a selected option, select2 must not add it again
    $this->assertStringNotContainsString('"text":"SNMP Secret 456"', $html);
});

test('index shows snmp bulk as no for v1', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);
    $device = Device::factory()->create();
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Snmp,
    ]);

    $html = $this->actingAs($admin)->get(route('device.edit.polling', ['device' => $device]))->assertOk()->getContent();

    // a disabled stand-in shows No in its place, the real select is hidden but still keeps and submits its value
    expect($html)->toMatch('/name="settings\[bulk\]"[^>]*x-show="!\(settingsData\[&quot;version&quot;\] === &quot;v1&quot; &amp;&amp; \(!currentSecretType/');
    expect($html)->toMatch('/<select class="form-control" disabled aria-label="SNMP bulk requests" x-show="settingsData\[&quot;version&quot;\] === &quot;v1&quot;[^"]*" x-cloak>\s*<option>No<\/option>/');
});

test('index renders snmp engine id', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);
    $device = Device::factory()->create(['snmpEngineID' => '80001f8880e9630000d61ff449']);

    $this->actingAs($admin)->get(route('device.edit.polling', ['device' => $device]))
        ->assertOk()
        ->assertSee('SNMP Engine ID')
        ->assertSee('80001f8880e9630000d61ff449');
});

test('index renders empty tabs when no methods configured', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);
    $device = Device::factory()->create();

    $response = $this->actingAs($admin)->get(route('device.edit.polling', ['device' => $device]));

    $response->assertOk();
    $response->assertSee('No Polling Methods Configured');
    $response->assertSee(route('device.edit.polling.store', $device), false);
    $response->assertViewHas('tabsConfig', function (array $config): bool {
        expect($config['initialTab'])->toBe('');
        expect($config['activeMethods'])->toBe([]);
        expect($config['allTypes'])->toHaveCount(count(PollingMethodType::known()));

        return true;
    });
});

test('index tabs config defaults to enabled snmp', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);
    $device = Device::factory()->create();
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Icmp,
        'enabled' => true,
    ]);
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Snmp,
        'enabled' => true,
        'last_check_successful' => false,
        'last_check_message' => 'Timeout: No Response',
    ]);
    $this->travel(2)->hours();

    $response = $this->actingAs($admin)->get(route('device.edit.polling', ['device' => $device]));

    $response->assertOk();
    $response->assertViewHas('tabsConfig', function (array $config): bool {
        expect($config['initialTab'])->toBe('snmp');
        expect($config['activeMethods'])->toEqualCanonicalizing(['snmp', 'icmp']);
        expect($config['methods']['snmp'])->toBe(['configured' => true, 'enabled' => true, 'affectsAvailability' => true, 'lastCheckSuccessful' => false, 'lastCheckMessage' => 'Timeout: No Response', 'lastCheckSince' => 'Since 2 hours ago']);
        expect($config['methods']['ipmi']['configured'])->toBeFalse();
        expect($config['allTypes'])->toContain(['type' => 'ipmi', 'label' => PollingMethodType::Ipmi->label()]);

        return true;
    });
});

test('index tabs config falls back when snmp disabled', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);
    $device = Device::factory()->create();
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Snmp,
        'enabled' => false,
    ]);
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Icmp,
        'enabled' => true,
    ]);

    $response = $this->actingAs($admin)->get(route('device.edit.polling', ['device' => $device, 'tab' => 'invalid']));

    $response->assertOk();
    $response->assertViewHas('tabsConfig', function (array $config): bool {
        expect($config['initialTab'])->toBe('icmp'); // first configured method
        expect($config['activeMethods'])->toBe(['icmp', 'snmp']);

        return true;
    });
});

test('index tabs config opens requested unconfigured tab', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);
    $device = Device::factory()->create();
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Icmp,
        'enabled' => true,
    ]);

    $response = $this->actingAs($admin)->get(route('device.edit.polling', ['device' => $device, 'tab' => 'ipmi']));

    $response->assertOk();
    $response->assertViewHas('tabsConfig', function (array $config): bool {
        expect($config['initialTab'])->toBe('ipmi');
        expect($config['activeMethods'])->toBe(['icmp', 'ipmi']);
        expect($config['methods']['ipmi']['configured'])->toBeFalse();

        return true;
    });
});

test('update returns json response when requested', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $device = Device::factory()->create();
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Icmp,
        'enabled' => true,
    ]);

    $response = $this->actingAs($admin)->putJson(
        route('device.edit.polling.update', ['device' => $device, 'methodType' => 'icmp']),
        [
            'enabled' => '1',
            'affects_availability' => '1',
            'force_save' => '1',
            'settings' => [],
        ]
    );

    $response->assertOk();
    $response->assertJson([
        'status' => 'ok',
        'message' => __('poller.method_updated'),
    ]);
    $response->assertJsonPath('method.type', 'icmp');
    $response->assertJsonPath('method.affects_availability', true);
    $response->assertSessionMissing('toasts');
    // the page shows the message itself
});

test('update flashes toast when redirecting', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $device = Device::factory()->create();
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Icmp,
        'enabled' => true,
    ]);

    $response = $this->actingAs($admin)->put(
        route('device.edit.polling.update', ['device' => $device, 'methodType' => 'icmp']),
        [
            'enabled' => '1',
            'affects_availability' => '1',
            'force_save' => '1',
            'settings' => [],
        ]
    );

    $response->assertRedirect(route('device.edit.polling', ['device' => $device, 'tab' => 'icmp']));
    $response->assertSessionHas('toasts', fn (array $toasts): bool => $toasts[0]['message'] === __('poller.method_updated'));
});

test('destroy returns json response when requested', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $device = Device::factory()->create();
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::UnixAgent,
        'enabled' => true,
    ]);

    $response = $this->actingAs($admin)->deleteJson(
        route('device.edit.polling.destroy', ['device' => $device, 'methodType' => 'unix-agent'])
    );

    $response->assertOk();
    $response->assertJson([
        'status' => 'ok',
        'message' => __('poller.method_removed'),
    ]);
    $response->assertSessionMissing('toasts');
    // the page shows the message itself
});

test('removing the last method marks the device up', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $device = Device::factory()->create(['status' => 0, 'status_reason' => 'icmp']);
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Icmp,
        'enabled' => true,
        'affects_availability' => true,
        'last_check_successful' => false,
    ]);

    $this->actingAs($admin)->deleteJson(
        route('device.edit.polling.destroy', ['device' => $device, 'methodType' => 'icmp'])
    )->assertOk();

    $device->refresh();
    expect($device->pollingMethods()->doesntExist())->toBeTrue();
    expect($device->status)->toEqual(1);
    // nothing affects availability
    expect($device->status_reason)->toBe('');
});

test('unknown method is shown generically and can be removed', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);
    $device = Device::factory()->create();
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => 'future-method',
        'enabled' => true,
    ]);

    $this->actingAs($admin)->get(route('device.edit.polling', $device))
        ->assertOk()
        ->assertSee('future-method')
        ->assertSee('activeMethods.length === 0 && false', false)
        ->assertSee(route('device.edit.polling.destroy', ['device' => $device, 'methodType' => 'future-method']));

    $this->actingAs($admin)->putJson(route('device.edit.polling.update', ['device' => $device, 'methodType' => 'future-method']), ['enabled' => '0'])
        ->assertNotFound();
    $this->actingAs($admin)->putJson(route('device.edit.polling.update', ['device' => $device, 'methodType' => 'unknown']), ['enabled' => '0'])
        ->assertNotFound();

    $this->actingAs($admin)->delete(route('device.edit.polling.destroy', ['device' => $device, 'methodType' => 'future-method']))
        ->assertRedirect(route('device.edit.polling', ['device' => $device]));
    $this->assertDatabaseMissing('device_polling_methods', ['device_id' => $device->device_id]);
});

test('unknown method with slash can be removed', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);
    $device = Device::factory()->create();
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => 'vendor/api',
    ]);

    $this->actingAs($admin)->delete(route('device.edit.polling.destroy', ['device' => $device, 'methodType' => 'vendor/api']))
        ->assertRedirect();
    $this->assertDatabaseMissing('device_polling_methods', ['device_id' => $device->device_id]);
});

test('secret of unknown type is still shown on its method', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);
    $device = Device::factory()->create();
    $secret = Secret::create(['description' => 'Future SNMP Secret', 'secret_type' => 'future-secret', 'data' => []]);
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Snmp,
        'secret_id' => $secret->id,
    ]);

    $this->actingAs($admin)->get(route('device.edit.polling', $device))
        ->assertOk()
        ->assertSee('<option value="' . $secret->id . '" selected>Future SNMP Secret</option>', false);
});

test('update secret data persists to database', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $secret = Secret::create([
        'description' => 'Original SNMP Secret',
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => 'public'],
    ]);

    $device = Device::factory()->create();
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Snmp,
        'secret_id' => $secret->id,
        'enabled' => true,
    ]);

    $response = $this->actingAs($admin)->putJson(
        route('device.edit.polling.update', ['device' => $device, 'methodType' => 'snmp']),
        [
            'enabled' => '1',
            'affects_availability' => '1',
            'force_save' => '1',
            'secret_id' => (string) $secret->id,
            'secret_mode' => 'edit',
            'description' => 'Updated SNMP Secret',
            'secret_type' => 'snmp-community',
            'secret_data' => [
                'community' => 'supersecret',
                'port' => 161,
                'retries' => 0,
                'timeout' => 1,
            ],
            'settings' => [
                'transport' => 'udp',
                'port' => 161,
                'timeout' => 1,
                'retries' => 0,
                'max_repeaters' => 0,
                'max_oid' => 10,
                'port_association_mode' => 'ifIndex',
            ],
        ]
    );

    $response->assertOk();
    expect($secret->fresh()->description)->toEqual('Updated SNMP Secret');
    expect($secret->fresh()->data['community'])->toEqual('supersecret');
});

test('update with new secret keeps shared secret', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $originalSecret = Secret::create([
        'description' => 'Shared SNMP Secret',
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => 'public'],
    ]);

    $device = Device::factory()->create();
    $pollingMethod = DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Snmp,
        'secret_id' => $originalSecret->id,
        'enabled' => true,
    ]);

    $otherDevice = Device::factory()->create();
    DevicePollingMethod::factory()->create([
        'device_id' => $otherDevice->device_id,
        'method_type' => PollingMethodType::Snmp,
        'secret_id' => $originalSecret->id,
        'enabled' => true,
    ]);

    $response = $this->actingAs($admin)->putJson(
        route('device.edit.polling.update', ['device' => $device, 'methodType' => 'snmp']),
        [
            'enabled' => '1',
            'affects_availability' => '1',
            'force_save' => '1',
            'secret_id' => (string) $originalSecret->id,
            'secret_mode' => 'new',
            'description' => 'New Dedicated Secret',
            'secret_type' => 'snmp-community',
            'secret_data' => [
                'community' => 'brandnew',
                'port' => 161,
                'retries' => 0,
                'timeout' => 1,
            ],
            'settings' => [
                'transport' => 'udp',
                'port' => 161,
                'timeout' => 1,
                'retries' => 0,
                'max_repeaters' => 0,
                'max_oid' => 10,
                'port_association_mode' => 'ifIndex',
            ],
        ]
    );

    $response->assertOk();
    expect($originalSecret->fresh()->data['community'])->toEqual('public');
    $newSecretId = $pollingMethod->fresh()->secret_id;
    $this->assertNotEquals($originalSecret->id, $newSecretId);
    $newSecret = Secret::find($newSecretId);
    expect($newSecret->description)->toEqual('New Dedicated Secret');
    expect($newSecret->data['community'])->toEqual('brandnew');
});

test('update with new secret rejects duplicate description', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $originalSecret = Secret::create([
        'description' => 'Shared SNMP Secret',
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => 'public'],
    ]);

    $device = Device::factory()->create();
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Snmp,
        'secret_id' => $originalSecret->id,
        'enabled' => true,
    ]);

    $otherDevice = Device::factory()->create();
    DevicePollingMethod::factory()->create([
        'device_id' => $otherDevice->device_id,
        'method_type' => PollingMethodType::Snmp,
        'secret_id' => $originalSecret->id,
        'enabled' => true,
    ]);

    // Attempting to create a new secret with the exact same description as the existing one
    $response = $this->actingAs($admin)->putJson(
        route('device.edit.polling.update', ['device' => $device, 'methodType' => 'snmp']),
        [
            'enabled' => '1',
            'affects_availability' => '1',
            'secret_id' => (string) $originalSecret->id,
            'secret_mode' => 'new',
            'description' => 'Shared SNMP Secret',
            'secret_type' => 'snmp-community',
            'secret_data' => [
                'community' => 'newcommunity',
                'port' => 161,
                'retries' => 0,
                'timeout' => 1,
            ],
            'settings' => [
                'transport' => 'udp',
                'port' => 161,
                'timeout' => 1,
                'retries' => 0,
                'max_repeaters' => 0,
                'max_oid' => 10,
                'port_association_mode' => 'ifIndex',
            ],
        ]
    );

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['description']);
});

test('edit secret with same description updates in place', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $secret = Secret::create([
        'description' => 'Solo SNMP Secret',
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => 'public'],
    ]);

    $device = Device::factory()->create();
    $pollingMethod = DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Snmp,
        'secret_id' => $secret->id,
        'enabled' => true,
    ]);

    $response = $this->actingAs($admin)->putJson(
        route('device.edit.polling.update', ['device' => $device, 'methodType' => 'snmp']),
        [
            'enabled' => '1',
            'affects_availability' => '1',
            'force_save' => '1',
            'secret_id' => (string) $secret->id,
            'secret_mode' => 'edit',
            'description' => 'Solo SNMP Secret',
            'secret_type' => 'snmp-community',
            'secret_data' => [
                'community' => 'updated_community',
                'port' => 161,
                'retries' => 0,
                'timeout' => 1,
            ],
            'settings' => [
                'transport' => 'udp',
                'port' => 161,
                'timeout' => 1,
                'retries' => 0,
                'max_repeaters' => 0,
                'max_oid' => 10,
                'port_association_mode' => 'ifIndex',
            ],
        ]
    );

    $response->assertOk();
    expect($pollingMethod->fresh()->secret_id)->toEqual($secret->id);
    expect($secret->fresh()->data['community'])->toEqual('updated_community');
    expect($secret->fresh()->description)->toEqual('Solo SNMP Secret');
});

test('update polling method unreachable returns422 with details', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $device = Device::factory()->create(['hostname' => 'unreachable-device.invalid']);
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Icmp,
        'enabled' => true,
    ]);

    $response = $this->actingAs($admin)->putJson(
        route('device.edit.polling.update', ['device' => $device, 'methodType' => 'icmp']),
        [
            'enabled' => '1',
            'affects_availability' => '1',
            'settings' => [],
        ]
    );

    $response->assertStatus(422);
    $response->assertJson([
        'status' => 'unreachable',
        'message' => __('poller.reachability_failed', [
            'hostname' => 'unreachable-device.invalid',
            'method' => 'ICMP',
        ]),
    ]);
});

test('update polling method force save bypasses unreachable', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $device = Device::factory()->create(['hostname' => 'unreachable-device.invalid']);
    $method = DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Icmp,
        'enabled' => true,
        'affects_availability' => false,
    ]);

    $response = $this->actingAs($admin)->putJson(
        route('device.edit.polling.update', ['device' => $device, 'methodType' => 'icmp']),
        [
            'enabled' => '1',
            'affects_availability' => '1',
            'force_save' => '1',
            'settings' => [],
        ]
    );

    $response->assertOk();
    expect($method->fresh()->affects_availability)->toBeTrue();
    expect($method->fresh()->last_check_successful)->toBeNull();
});

test('update availability change is logged', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $device = Device::factory()->create(['status' => false, 'status_reason' => 'icmp']);
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Icmp,
        'enabled' => true,
        'affects_availability' => true,
        'last_check_successful' => false,
    ]);

    $response = $this->actingAs($admin)->putJson(
        route('device.edit.polling.update', ['device' => $device, 'methodType' => 'icmp']),
        [
            'enabled' => '1',
            'affects_availability' => '0',
            'force_save' => '1',
            'settings' => [],
        ]
    );

    $response->assertOk();
    expect($device->fresh()->status)->toBeTrue();
    $this->assertDatabaseHas('eventlog', [
        'device_id' => $device->device_id,
        'message' => 'Device status changed to Up from icmp check.',
    ]);
});

test('update cannot restore masked values from inaccessible secret', function () {
    $user = User::factory()->create(['enabled' => 1]);
    $user->givePermissionTo(['device.update', 'secret.update']);

    $foreignSecret = Secret::create([
        'description' => 'Foreign SNMP Secret',
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => 'foreign-community'],
    ]);
    DevicePollingMethod::factory()->create([
        'device_id' => Device::factory()->create()->device_id,
        'method_type' => PollingMethodType::Snmp,
        'secret_id' => $foreignSecret->id,
    ]);

    $device = Device::factory()->create();
    $method = DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Snmp,
        'secret_id' => null,
    ]);

    $response = $this->actingAs($user)->putJson(
        route('device.edit.polling.update', ['device' => $device, 'methodType' => 'snmp']),
        [
            'enabled' => '1',
            'secret_id' => (string) $foreignSecret->id,
            'secret_mode' => 'new',
            'description' => 'Stolen',
            'secret_type' => 'snmp-community',
            'secret_data' => [
                'community' => SecretDefinition::MASK,
            ],
        ]
    );

    $response->assertNotFound();
    expect($method->fresh()->secret_id)->toBeNull();
    $this->assertDatabaseMissing('secrets', ['description' => 'Stolen']);
});

test('store polling method unreachable returns422', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $device = Device::factory()->create(['hostname' => 'unreachable-device2.invalid']);

    $response = $this->actingAs($admin)->postJson(
        route('device.edit.polling.store', ['device' => $device]),
        [
            'method_type' => 'icmp',
            'settings' => [],
        ]
    );

    $response->assertStatus(422);
    $response->assertJson([
        'status' => 'unreachable',
        'message' => __('poller.reachability_failed', [
            'hostname' => 'unreachable-device2.invalid',
            'method' => 'ICMP',
        ]),
    ]);
    $this->assertDatabaseMissing('device_polling_methods', [
        'device_id' => $device->device_id,
        'method_type' => 'icmp',
    ]);
});

test('store polling method force save bypasses unreachable', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $device = Device::factory()->create(['hostname' => 'unreachable-device3.invalid']);

    $response = $this->actingAs($admin)->postJson(
        route('device.edit.polling.store', ['device' => $device]),
        [
            'method_type' => 'icmp',
            'force_save' => '1',
            'settings' => [],
        ]
    );

    $response->assertOk();
    $response->assertJsonPath('message', __('poller.method_added'));
    $response->assertSessionMissing('toasts');
    // the page shows the message itself
    $this->assertDatabaseHas('device_polling_methods', [
        'device_id' => $device->device_id,
        'method_type' => 'icmp',
        'last_check_successful' => null,
    ]);
});

test('update polling method settings only stores overrides and falls back to defaults', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $device = Device::factory()->create();

    $method = DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::UnixAgent,
        'enabled' => true,
        'settings' => [],
    ]);

    // 1. Update with empty port/timeout (should store empty array, not fallback values)
    $response = $this->actingAs($admin)->putJson(
        route('device.edit.polling.update', ['device' => $device, 'methodType' => 'unix-agent']),
        [
            'enabled' => '1',
            'affects_availability' => '0',
            'force_save' => '1',
            'settings' => [
                'port' => '',
                'timeout' => '',
            ],
        ]
    );
    $response->assertOk();

    $freshMethod = $method->fresh();
    expect($freshMethod->settings)->toEqual([]);
    $config = $device->fresh()->polling()->get(PollingMethodType::UnixAgent);
    expect($config)->toBeInstanceOf(UnixAgentConfig::class);
    expect($config->port)->toEqual(6556);
    expect($config->timeout)->toEqual(10);

    // 2. Update with custom port override
    $response2 = $this->actingAs($admin)->putJson(
        route('device.edit.polling.update', ['device' => $device, 'methodType' => 'unix-agent']),
        [
            'enabled' => '1',
            'affects_availability' => '0',
            'force_save' => '1',
            'settings' => [
                'port' => 6557,
                'timeout' => '',
            ],
        ]
    );
    $response2->assertOk();

    $freshMethod = $method->fresh();
    expect($freshMethod->settings)->toEqual(['port' => 6557]);
    $config = $device->fresh()->polling()->get(PollingMethodType::UnixAgent);
    expect($config)->toBeInstanceOf(UnixAgentConfig::class);
    expect($config->port)->toEqual(6557);

    // 3. Clear the override by submitting empty port
    $response3 = $this->actingAs($admin)->putJson(
        route('device.edit.polling.update', ['device' => $device, 'methodType' => 'unix-agent']),
        [
            'enabled' => '1',
            'affects_availability' => '0',
            'force_save' => '1',
            'settings' => [
                'port' => '',
                'timeout' => '',
            ],
        ]
    );
    $response3->assertOk();

    $freshMethod = $method->fresh();
    expect($freshMethod->settings)->toEqual([]);
    $config = $device->fresh()->polling()->get(PollingMethodType::UnixAgent);
    expect($config)->toBeInstanceOf(UnixAgentConfig::class);
    expect($config->port)->toEqual(6556);
});

test('update icmp settings saves ip version', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $device = Device::factory()->create(['hostname' => 'icmp-device.example.com']);
    $method = DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Icmp,
        'enabled' => true,
        'affects_availability' => true,
        'settings' => [],
    ]);

    $response = $this->actingAs($admin)->putJson(
        route('device.edit.polling.update', ['device' => $device, 'methodType' => 'icmp']),
        [
            'enabled' => '1',
            'affects_availability' => '1',
            'force_save' => '1',
            'settings' => [
                'ip_version' => 'ipv6',
            ],
        ]
    );
    $response->assertOk();

    $freshMethod = $method->fresh();
    expect($freshMethod->settings)->toEqual(['ip_version' => 'ipv6']);
    $config = $device->fresh()->polling()->icmp();
    expect($config->ipVersion)->toBe('ipv6');
    $icmpMethod = app(PollingMethodRegistry::class)->get(PollingMethodType::Icmp);
    expect($icmpMethod)->toBeInstanceOf(IcmpPollingMethod::class);
    expect($icmpMethod->resolveAddressFamily($device->fresh(), $config))->toBe(AddressFamily::IPv6);
});

test('update stores only user set settings', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $device = Device::factory()->create();
    $method = DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Snmp,
        'settings' => ['transport' => 'tcp', 'max_repeaters' => 20],
    ]);

    $this->actingAs($admin)->putJson(route('device.edit.polling.update', ['device' => $device, 'methodType' => 'snmp']), [
        'force_save' => '1',
        'settings' => ['transport' => '', 'port' => '161', 'max_repeaters' => '', 'context' => 'vrf-a'],
    ])->assertOk();

    // empty fields (the "Default" option) are not stored, even values matching a default are kept
    expect($method->fresh()->settings)->toBe(['port' => 161, 'context' => 'vrf-a']);
});

test('update without secret mode keeps secret', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $secret = Secret::create([
        'description' => 'Kept Secret',
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => 'kept'],
    ]);
    $device = Device::factory()->create();
    $method = DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Snmp,
        'secret_id' => $secret->id,
    ]);

    $this->actingAs($admin)->putJson(route('device.edit.polling.update', ['device' => $device, 'methodType' => 'snmp']), [
        'enabled' => '0',
    ])->assertOk();

    expect($method->fresh()->secret_id)->toBe($secret->id);
    expect($method->fresh()->enabled)->toBeFalse();
});

test('secret permissions follow secret mode', function () {
    $user = User::factory()->create(['enabled' => 1]);
    $user->givePermissionTo('device.update');
    Permission::findOrCreate('device.viewAll');
    $user->givePermissionTo('device.viewAll');

    // may use any secret
    $secret = Secret::create([
        'description' => 'Usable Secret',
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => 'usable'],
    ]);
    $device = Device::factory()->create();
    $store = route('device.edit.polling.store', ['device' => $device]);

    // creating a secret needs secret.create
    $this->actingAs($user)->postJson($store, [
        'method_type' => 'snmp',
        'force_save' => '1',
        'secret_mode' => 'new',
        'description' => 'Not Allowed',
        'secret_type' => 'snmp-community', 'secret_data' => ['community' => 'nope'],
    ])->assertForbidden();

    // using an existing secret does not
    $this->actingAs($user)->postJson($store, [
        'method_type' => 'snmp',
        'force_save' => '1',
        'secret_mode' => 'existing',
        'secret_id' => $secret->id,
    ])->assertOk();

    expect($device->fresh()->pollingMethod(PollingMethodType::Snmp)->secret_id)->toBe($secret->id);
    $this->assertDatabaseMissing('secrets', ['description' => 'Not Allowed']);
});

test('tab parameter is not injected into script', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);
    $device = Device::factory()->create();
    DevicePollingMethod::factory()->create(['device_id' => $device->device_id, 'method_type' => PollingMethodType::Icmp, 'enabled' => true]);

    $response = $this->actingAs($admin)->get(route('device.edit.polling', ['device' => $device, 'tab' => "');alert(1);//"]));

    $response->assertOk();
    $this->assertStringNotContainsString('alert(1)', $response->getContent());
});

test('secret data is loaded masked without unmask', function () {
    $device = Device::factory()->create();
    $secret = Secret::create([
        'description' => 'unmask-test',
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => 'topsecret'],
    ]);
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Snmp,
        'enabled' => true,
        'secret_id' => $secret->id,
    ]);
    $user = User::factory()->create(['enabled' => 1]);
    $user->givePermissionTo(['device.update', 'secret.view', 'secret.update']);
    \DB::table('devices_perms')->insert(['user_id' => $user->user_id, 'device_id' => $device->device_id]);

    $this->actingAs($user)->getJson(route('secrets.show', $secret))
        ->assertOk()
        ->assertJsonPath('description', 'unmask-test')
        ->assertJsonPath('usage_count', 1)
        ->assertJsonPath('secret_type', 'snmp-community')
        ->assertJsonPath('data.community', SecretDefinition::MASK);

    $user->givePermissionTo('secret.unmask');
    $this->actingAs($user)->getJson(route('secrets.show', $secret))
        ->assertOk()
        ->assertJsonPath('data.community', 'topsecret');
});

test('secret data is not loaded without access', function () {
    $secret = Secret::create([
        'description' => 'other-device-secret',
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => 'topsecret'],
    ]);
    DevicePollingMethod::factory()->create([
        'device_id' => Device::factory()->create()->device_id,
        'method_type' => PollingMethodType::Snmp,
        'secret_id' => $secret->id,
    ]);
    $user = User::factory()->create(['enabled' => 1]);
    $user->givePermissionTo(['device.update', 'secret.unmask']);

    $this->actingAs($user)->getJson(route('secrets.show', $secret))->assertForbidden();

    // can view secrets, but not the secrets of devices it can't access
    $user->givePermissionTo('secret.view');
    $this->actingAs($user)->getJson(route('secrets.show', $secret))->assertNotFound();
});

test('save response does not contain secret data', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);
    $device = Device::factory()->create();

    $content = $this->actingAs($admin)->postJson(route('device.edit.polling.store', ['device' => $device]), [
        'method_type' => 'snmp',
        'enabled' => 1,
        'force_save' => 1,
        'secret_mode' => 'new',
        'description' => 'new-secret',
        'secret_type' => 'snmp-community', 'secret_data' => ['community' => 'topsecret'],
    ])->assertOk()->getContent();

    $this->assertStringContainsString('new-secret', $content);
    $this->assertStringNotContainsString('topsecret', $content);
});

test('default secret description on edit page is unique', function () {
    $device = Device::factory()->create(['hostname' => 'probe.example.com']);
    Secret::create([
        'description' => 'SNMP probe.example.com',
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => 'x'],
    ]);
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $content = $this->actingAs($admin)->get(route('device.edit.polling', $device))->assertOk()->getContent();

    $this->assertStringContainsString('SNMP probe.example.com (2)', $content);
});

test('secret of another type is rejected', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);
    $device = Device::factory()->create();
    $ipmi = Secret::create(['description' => 'ipmi for snmp', 'secret_type' => SecretType::Ipmi, 'data' => ['username' => 'admin']]);

    $this->actingAs($admin)->postJson(route('device.edit.polling.store', ['device' => $device]), [
        'method_type' => 'snmp',
        'force_save' => '1',
        'secret_mode' => 'existing',
        'secret_id' => $ipmi->id,
    ])->assertUnprocessable()->assertJsonValidationErrors(['secret_id']);

    expect($device->pollingMethod(PollingMethodType::Snmp))->toBeNull();
});

test('new secret type is chosen and its fields are validated', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);
    $device = Device::factory()->create(['hostname' => 'new-secret-type.example.com']);
    $store = route('device.edit.polling.store', ['device' => $device]);
    $request = [
        'method_type' => 'snmp',
        'force_save' => '1',
        'secret_mode' => 'new',
        'description' => 'new-secret-type v3',
    ];

    // with two types to choose from, the type is required
    $this->actingAs($admin)->postJson($store, [...$request, 'secret_data' => ['community' => 'public']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['secret_type']);

    // the fields of the chosen type are validated
    $this->actingAs($admin)->postJson($store, [...$request, 'secret_type' => 'snmp-v3', 'secret_data' => ['community' => 'public']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['secret_data.authname']);

    $this->actingAs($admin)->postJson($store, [...$request, 'secret_type' => 'snmp-v3', 'secret_data' => ['authlevel' => 'noAuthNoPriv', 'authname' => 'v3user', 'community' => 'ignored']])
        ->assertOk();

    $snmp = $device->pollingMethod(PollingMethodType::Snmp);
    expect($snmp->secret->secret_type)->toBe(SecretType::SnmpV3);
    expect($snmp->secret->data)->toBe(['authlevel' => 'noAuthNoPriv', 'authname' => 'v3user']);
    expect($device->polling()->snmp()->version)->toBe('v3');
    // the version follows from the secret
});

test('community version is only shown for communities', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);
    $device = Device::factory()->create();
    $community = Secret::create(['description' => 'community v1', 'secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'public']]);
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Snmp,
        'secret_id' => $community->id,
    ]);

    $this->actingAs($admin)->putJson(route('device.edit.polling.update', ['device' => $device, 'methodType' => 'snmp']), [
        'force_save' => '1',
        'settings' => ['version' => 'v1'],
    ])->assertOk();
    expect($device->polling()->snmp()->version)->toBe('v1');

    $snmp = $this->actingAs($admin)->get(route('device.edit.polling', $device))->assertOk()->viewData('allMethods')->firstWhere('type', 'snmp');
    $field = collect($snmp['settings_fields'])->firstWhere('key', 'version');
    expect($field['visible_if_expression'])->toBe('(!currentSecretType || ["snmp-community"].includes(currentSecretType))');
    expect($snmp['secret_type_by_id'][$community->id])->toBe('snmp-community');
});