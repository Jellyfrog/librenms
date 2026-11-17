<?php

/**
 * DeviceConfigTabTest.php
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2026 LibreNMS
 */

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use App\Http\Controllers\Device\Tabs\ConfigController;
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->originalProviders = \App\ConfigBackup\ConfigBackupManager::$providers;
    \App\ConfigBackup\ConfigBackupManager::$providers = [
        \App\ConfigBackup\Providers\UnimusProvider::class,
        \App\ConfigBackup\Providers\OxidizedProvider::class,
    ];

    Role::findOrCreate('admin');
    Role::findOrCreate('user');

    LibrenmsConfig::set('unimus.enabled', true);
    LibrenmsConfig::set('unimus.url', 'http://unimus:8085');
    LibrenmsConfig::set('unimus.api_version', 'v2');
    LibrenmsConfig::set('unimus.token', 'test-token');
});

afterEach(function () {
    \App\ConfigBackup\ConfigBackupManager::$providers = $this->originalProviders;
});

function admin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

test('config tab visible when unimus configured', function () {
    $device = Device::factory()->create();

    $this->actingAs(admin());

    expect(app(ConfigController::class)->visible($device))->toBeTrue();
});

test('config tab hidden when unimus disabled', function () {
    LibrenmsConfig::set('unimus.enabled', false);

    $device = Device::factory()->create();

    $this->actingAs(admin());

    expect(app(ConfigController::class)->visible($device))->toBeFalse();
});

test('config tab hidden without permission', function () {
    $device = Device::factory()->create();

    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');
    $this->actingAs($user);

    expect(app(ConfigController::class)->visible($device))->toBeFalse();
});

test('user without permission gets forbidden', function () {
    $device = Device::factory()->create();

    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('device.config.backups', ['device' => $device->device_id, 'page' => 0]))
        ->assertForbidden();
});

test('backups endpoint returns backup list', function () {
    $device = Device::factory()->create();

    Http::fake([
        'unimus:8085/api/v2/devices/findByAddress/*' => Http::response(['data' => ['id' => 7]], 200),
        'unimus:8085/api/v2/devices/7/backups*' => Http::response([
            'data' => [
                ['id' => 2, 'validSince' => 200, 'validUntil' => null, 'type' => 'TEXT', 'bytes' => base64_encode('config')],
            ],
            'paginator' => ['totalCount' => 1, 'totalPages' => 1, 'page' => 0, 'size' => 50],
        ], 200),
    ]);

    $this->actingAs(admin())
        ->get(route('device.config.backups', ['device' => $device->device_id, 'page' => 0]))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('backups.0.id', '2')
        ->assertJsonPath('backups.0.content', null);
});

test('backups endpoint returns404 when device not in unimus', function () {
    $device = Device::factory()->create();

    Http::fake(['*' => Http::response(['code' => 404], 404)]);

    $this->actingAs(admin())
        ->get(route('device.config.backups', ['device' => $device->device_id, 'page' => 0]))
        ->assertNotFound()
        ->assertJsonPath('error', 'device_not_found');
});

test('backup endpoint returns content', function () {
    $device = Device::factory()->create();

    Http::fake([
        'unimus:8085/api/v2/devices/findByAddress/*' => Http::response(['data' => ['id' => 7]], 200),
        'unimus:8085/api/v2/devices/7/backups*' => Http::response([
            'data' => [
                ['id' => 2, 'validSince' => 200, 'validUntil' => null, 'type' => 'TEXT', 'bytes' => base64_encode('interface eth0')],
            ],
            'paginator' => ['totalCount' => 1, 'totalPages' => 1, 'page' => 0, 'size' => 50],
        ], 200),
    ]);

    $this->actingAs(admin())
        ->get(route('device.config.backup', ['device' => $device->device_id]) . '?backup=2')
        ->assertOk()
        ->assertJsonPath('content', 'interface eth0');
});

test('backup endpoint rejects invalid id', function () {
    $device = Device::factory()->create();

    $this->actingAs(admin())
        ->getJson(route('device.config.backup', ['device' => $device->device_id]) . '?backup=invalid%23character')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['backup']);
});

test('diff endpoint validates parameters', function () {
    $device = Device::factory()->create();

    $this->actingAs(admin())
        ->getJson(route('device.config.diff', ['device' => $device->device_id]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['orig', 'rev']);

    $this->actingAs(admin())
        ->getJson(route('device.config.diff', ['device' => $device->device_id, 'orig' => 1, 'rev' => 1]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['rev']);
});

test('diff endpoint returns normalized groups', function () {
    $device = Device::factory()->create();

    Http::fake([
        'unimus:8085/api/v2/backups/diff*' => Http::response([
            'data' => [
                'origDeviceInfo' => ['id' => 0, 'address' => '192.168.1.1', 'type' => 'IOS'],
                'revDeviceInfo' => ['id' => 0, 'address' => '192.168.1.1', 'type' => 'IOS'],
                'lineGroups' => [
                    [
                        'type' => 'INSERTED',
                        'originalLines' => [],
                        'revisedLines' => [['number' => 5, 'text' => 'ntp server 10.0.0.1']],
                    ],
                ],
            ],
        ], 200),
    ]);

    // Unimus backup ids are zero-based, the very first backup has id 0
    $this->actingAs(admin())
        ->getJson(route('device.config.diff', ['device' => $device->device_id, 'orig' => 0, 'rev' => 2]))
        ->assertOk()
        ->assertJsonPath('groups.0.type', 'INSERTED')
        ->assertJsonPath('groups.0.revised.0.text', 'ntp server 10.0.0.1');
});

test('data method returns prepared urls and messages', function () {
    $device = Device::factory()->create(['os' => 'pfsense']);

    Http::fake([
        'unimus:8085/api/v2/devices/findByAddress/*' => Http::response(['data' => ['id' => 7]], 200),
    ]);

    $data = app(ConfigController::class)->data($device, new \Illuminate\Http\Request());

    $this->assertArrayNotHasKey('latest', $data);
    $this->assertArrayNotHasKey('backups', $data);
    $this->assertArrayNotHasKey('total', $data);
    $this->assertArrayNotHasKey('totalPages', $data);
    $this->assertArrayNotHasKey('provider', $data);
    expect($data)->toHaveKey('urls');
    expect($data)->toHaveKey('messages');
    expect($data['hostname'])->toEqual($device->hostname);
    expect($data['os'])->toBe('pfsense');
    expect($data['config_highlighting'])->toBe('xml');
});

test('data method leaves unconfigured highlighting null', function () {
    $device = Device::factory()->create(['os' => 'ios']);

    Http::fake([
        'unimus:8085/api/v2/devices/findByAddress/*' => Http::response(['data' => ['id' => 7]], 200),
    ]);

    $data = app(ConfigController::class)->data($device, new \Illuminate\Http\Request());

    expect($data['os'])->toBe('ios');
    expect($data['config_highlighting'])->toBeNull();
});

test('config tab renders highlighted code with line numbers', function () {
    $device = Device::factory()->create(['os' => 'pfsense']);

    Http::fake([
        'unimus:8085/api/v2/devices/findByAddress/*' => Http::response(['data' => ['id' => 7]], 200),
    ]);

    $this->actingAs(admin())
        ->get(route('device', ['device' => $device, 'tab' => 'config']))
        ->assertOk()
        ->assertSee('data-config-backups', false)
        ->assertSee('window.LibreNMS.loadConfigHighlight()', false)
        ->assertSee('class="config-highlight ', false)
        ->assertSee('x-config-highlight="selected.content"', false)
        ->assertSee('data-os="pfsense"', false)
        ->assertSee('data-config-highlighting="xml"', false);
});

test('latest endpoint returns latest backup', function () {
    $device = Device::factory()->create();

    Http::fake([
        'unimus:8085/api/v2/devices/findByAddress/*' => Http::response(['data' => ['id' => 7]], 200),
        'unimus:8085/api/v2/devices/7/backups/latest' => Http::response([
            'data' => ['id' => 99, 'validSince' => 300, 'validUntil' => null, 'type' => 'TEXT', 'bytes' => base64_encode('latest content')],
        ], 200),
    ]);

    $this->actingAs(admin())
        ->get(route('device.config.backup', ['device' => $device->device_id]))
        ->assertOk()
        ->assertJsonPath('id', '99')
        ->assertJsonPath('content', 'latest content');
});

test('backup endpoint substitutes invalid utf8 in content', function () {
    $device = Device::factory()->create();

    Http::fake([
        'unimus:8085/api/v2/devices/findByAddress/*' => Http::response(['data' => ['id' => 7]], 200),
        'unimus:8085/api/v2/devices/7/backups*' => Http::response([
            'data' => [
                ['id' => 2, 'validSince' => 200, 'validUntil' => null, 'type' => 'TEXT', 'bytes' => base64_encode("description caf\xE9 ferm\xE9")],
            ],
            'paginator' => ['totalCount' => 1, 'totalPages' => 1, 'page' => 0, 'size' => 50],
        ], 200),
    ]);

    $this->actingAs(admin())
        ->get(route('device.config.backup', ['device' => $device->device_id]) . '?backup=2')
        ->assertOk()
        ->assertJsonPath('content', "description caf\u{FFFD} ferm\u{FFFD}");
});

test('diff endpoint substitutes invalid utf8 in text', function () {
    // The Oxidized diff is plain text, so raw non-UTF-8 bytes reach the
    // controller unchanged (unlike the Unimus JSON API).
    LibrenmsConfig::set('unimus.enabled', false);
    LibrenmsConfig::set('oxidized.enabled', true);
    LibrenmsConfig::set('oxidized.url', 'http://oxidized:3000');

    $device = Device::factory()->create(['hostname' => 'router1']);

    $diff = "--- a/config\n+++ b/config\n@@ -1,1 +1,1 @@\n-description old\n+description caf\xE9\n";

    Http::fake([
        'oxidized:3000/node/show/*' => Http::response(['name' => 'router1', 'full_name' => 'router1'], 200),
        'oxidized:3000/node/version/diffs*' => Http::response($diff, 200),
    ]);

    $this->actingAs(admin())
        ->getJson(route('device.config.diff', ['device' => $device->device_id, 'orig' => 'aaa111', 'rev' => 'bbb222']))
        ->assertOk()
        ->assertJsonPath('groups.1.type', 'INSERTED')
        ->assertJsonPath('groups.1.revised.0.text', "description caf\u{FFFD}");
});