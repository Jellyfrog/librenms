<?php

/**
 * ConfigBackupDesignContractTest.php
 *
 * Contract and invariant test suite enforcing the specifications in
 * doc/Developing/Config-Backups-UI-UX.md without coupling to internal
 * client-side implementation details.
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
use App\ConfigBackup\ConfigBackupManager;
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->originalProviders = ConfigBackupManager::$providers;
    ConfigBackupManager::$providers = [
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
    ConfigBackupManager::$providers = $this->originalProviders;
});

function admin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

test('invariant1 chronological ordering', function () {
    $device = Device::factory()->create();

    Http::fake([
        'unimus:8085/api/v2/devices/findByAddress/*' => Http::response(['data' => ['id' => 7]], 200),
        'unimus:8085/api/v2/devices/7/backups*' => Http::response([
            'data' => [
                ['id' => 20, 'validSince' => 500, 'validUntil' => null, 'type' => 'TEXT'],
                ['id' => 19, 'validSince' => 400, 'validUntil' => 500, 'type' => 'TEXT'],
                ['id' => 18, 'validSince' => 300, 'validUntil' => 400, 'type' => 'TEXT'],
            ],
            'paginator' => ['totalCount' => 3, 'totalPages' => 1, 'page' => 0, 'size' => 50],
        ], 200),
    ]);

    $response = $this->actingAs(admin())
        ->getJson(route('device.config.backups', ['device' => $device->device_id, 'page' => 0]))
        ->assertOk();

    $backups = $response->json('backups');
    expect($backups)->toHaveCount(3);
    expect($backups[0]['id'])->toBe('20');
    expect($backups[1]['id'])->toBe('19');
    expect($backups[2]['id'])->toBe('18');
    expect($backups[0]['date'])->toBeGreaterThan($backups[1]['date']);
    expect($backups[1]['date'])->toBeGreaterThan($backups[2]['date']);
});

test('invariant5 diff rejects identical revisions', function () {
    $device = Device::factory()->create();

    $this->actingAs(admin())
        ->getJson(route('device.config.diff', ['device' => $device->device_id, 'orig' => 10, 'rev' => 10]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['rev']);
});

test('invariant9 backup list omits full content', function () {
    $device = Device::factory()->create();

    Http::fake([
        'unimus:8085/api/v2/devices/findByAddress/*' => Http::response(['data' => ['id' => 7]], 200),
        'unimus:8085/api/v2/devices/7/backups*' => Http::response([
            'data' => [
                ['id' => 1, 'validSince' => 100, 'validUntil' => null, 'type' => 'TEXT', 'bytes' => base64_encode('large config')],
            ],
            'paginator' => ['totalCount' => 1, 'totalPages' => 1, 'page' => 0, 'size' => 50],
        ], 200),
    ]);

    $this->actingAs(admin())
        ->getJson(route('device.config.backups', ['device' => $device->device_id, 'page' => 0]))
        ->assertOk()
        ->assertJsonPath('backups.0.content', null);
});

test('invariant14 diff normalizes to standard groups', function () {
    $device = Device::factory()->create();

    Http::fake([
        'unimus:8085/api/v2/backups/diff*' => Http::response([
            'data' => [
                'origDeviceInfo' => ['id' => 0, 'address' => '10.0.0.1', 'type' => 'IOS'],
                'revDeviceInfo' => ['id' => 0, 'address' => '10.0.0.1', 'type' => 'IOS'],
                'lineGroups' => [
                    [
                        'type' => 'COMMON',
                        'originalLines' => [['number' => 1, 'text' => 'hostname router']],
                        'revisedLines' => [['number' => 1, 'text' => 'hostname router']],
                    ],
                    [
                        'type' => 'INSERTED',
                        'originalLines' => [],
                        'revisedLines' => [['number' => 2, 'text' => 'interface GigabitEthernet0/1']],
                    ],
                    [
                        'type' => 'DELETED',
                        'originalLines' => [['number' => 2, 'text' => 'interface FastEthernet0/1']],
                        'revisedLines' => [],
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs(admin())
        ->getJson(route('device.config.diff', ['device' => $device->device_id, 'orig' => 1, 'rev' => 2]))
        ->assertOk();

    $groups = $response->json('groups');
    expect($groups)->toHaveCount(3);
    expect($groups[0]['type'])->toBe('COMMON');
    expect($groups[1]['type'])->toBe('INSERTED');
    expect($groups[2]['type'])->toBe('DELETED');
    expect($groups[0]['original'][0]['line'])->toBe(1);
    expect($groups[0]['original'][0]['text'])->toBe('hostname router');
    expect($groups[1]['revised'][0]['line'])->toBe(2);
    expect($groups[1]['revised'][0]['text'])->toBe('interface GigabitEthernet0/1');
});

test('invariant13 identical diff contract', function () {
    $device = Device::factory()->create();

    Http::fake([
        'unimus:8085/api/v2/backups/diff*' => Http::response([
            'data' => [
                'origDeviceInfo' => ['id' => 0, 'address' => '10.0.0.1', 'type' => 'IOS'],
                'revDeviceInfo' => ['id' => 0, 'address' => '10.0.0.1', 'type' => 'IOS'],
                'lineGroups' => [
                    [
                        'type' => 'COMMON',
                        'originalLines' => [['number' => 1, 'text' => 'hostname switch']],
                        'revisedLines' => [['number' => 1, 'text' => 'hostname switch']],
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs(admin())
        ->getJson(route('device.config.diff', ['device' => $device->device_id, 'orig' => 1, 'rev' => 2]))
        ->assertOk();

    $groups = $response->json('groups');
    expect($groups)->toHaveCount(1);
    expect($groups[0]['type'])->toBe('COMMON');
    expect(array_filter($groups, fn ($g) => in_array($g['type'], ['INSERTED', 'DELETED', 'CHANGED'])))->toBeEmpty();
});

test('invariant4 binary backups flagged', function () {
    $device = Device::factory()->create();

    Http::fake([
        'unimus:8085/api/v2/devices/findByAddress/*' => Http::response(['data' => ['id' => 7]], 200),
        'unimus:8085/api/v2/devices/7/backups*' => Http::response([
            'data' => [
                ['id' => 5, 'validSince' => 100, 'validUntil' => null, 'type' => 'BINARY'],
            ],
            'paginator' => ['totalCount' => 1, 'totalPages' => 1, 'page' => 0, 'size' => 50],
        ], 200),
    ]);

    $this->actingAs(admin())
        ->getJson(route('device.config.backups', ['device' => $device->device_id, 'page' => 0]))
        ->assertOk()
        ->assertJsonPath('backups.0.type', 'BINARY');
});

test('invariant10 history boundaries', function () {
    $device = Device::factory()->create();

    $this->actingAs(admin())
        ->getJson(route('device.config.backups', ['device' => $device->device_id, 'page' => -1]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['page']);
});

test('view enshrines keyboard shortcuts and interaction contract', function () {
    $device = Device::factory()->create();

    Http::fake([
        'unimus:8085/api/v2/devices/findByAddress/*' => Http::response(['data' => ['id' => 7]], 200),
    ]);

    $view = $this->actingAs(admin())
        ->get(route('device', ['device' => $device, 'tab' => 'config']))
        ->assertOk();

    // Alpine & Viewer Components
    $view->assertSee('data-config-backups', false);
    $view->assertSee('x-config-highlight="selected.content"', false);
    $view->assertSee('class="config-highlight ', false);
    $view->assertSee('x-ignore', false);

    // Diff Table Header Contract (Concept B)
    $view->assertSee('diffRangeSummaryText', false);

    // Keyboard Shortcut Localization Strings & Modifiers
    $view->assertSee('x-text="modifierKey"', false);
    $view->assertSee(__('config_backups.navigate_history'));
    $view->assertSee(__('config_backups.compare_revisions'));
    $view->assertSee(__('config_backups.shortcut_older'));
    $view->assertSee(__('config_backups.shortcut_newer'));
    $view->assertSee(__('config_backups.shortcut_toggle_diff'));
    $view->assertSee(__('config_backups.shortcut_shift_click'));
    $view->assertSee(__('config_backups.shortcut_reverse_diff'));
    $view->assertSee(__('config_backups.shortcut_expand_older'));
    $view->assertSee(__('config_backups.shortcut_expand_newer'));
});