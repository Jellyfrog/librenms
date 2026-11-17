<?php

/**
 * UnimusProviderTest.php
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
use App\ApiClients\Unimus;
use App\ConfigBackup\Providers\UnimusProvider;
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use LibreNMS\Interfaces\ConfigBackupProvider;

beforeEach(function () {
    LibrenmsConfig::set('unimus.enabled', true);
    LibrenmsConfig::set('unimus.url', 'http://unimus:8085');
    LibrenmsConfig::set('unimus.api_version', 'v2');
    LibrenmsConfig::set('unimus.token', 'test-token');
});

function makeProvider(): UnimusProvider
{
    return new UnimusProvider(new Unimus());
}

function makeDevice(): Device
{
    $device = new Device(['hostname' => 'router.example.com']);
    $device->device_id = 42;

    return $device;
}

test('is configured delegates to client', function () {
    expect(UnimusProvider::isConfigured())->toBeTrue();

    LibrenmsConfig::set('unimus.enabled', false);
    expect(UnimusProvider::isConfigured())->toBeFalse();
});

test('supports any device', function () {
    expect(makeProvider()->supportsDevice(makeDevice()))->toBeTrue();
});

test('backups stringifies ids', function () {
    Http::fake([
        'unimus:8085/api/v2/devices/findByAddress/*' => Http::response(['data' => ['id' => 7]], 200),
        'unimus:8085/api/v2/devices/7/backups*' => Http::response([
            'data' => [
                ['id' => 2, 'validSince' => 200, 'validUntil' => null, 'type' => 'TEXT', 'bytes' => base64_encode('config')],
                ['id' => 0, 'validSince' => 100, 'validUntil' => 200, 'type' => 'TEXT', 'bytes' => base64_encode('older')],
            ],
            'paginator' => ['totalCount' => 2, 'totalPages' => 1, 'page' => 0, 'size' => 50],
        ], 200),
    ]);

    $list = makeProvider()->backups(makeDevice());

    expect($list['backups'][0]['id'])->toBe('2');
    expect($list['backups'][1]['id'])->toBe('0', 'zero-based ids survive stringification');
    expect($list['total'])->toBe(2);
});

test('latest stringifies id and includes content', function () {
    Http::fake([
        'unimus:8085/api/v2/devices/findByAddress/*' => Http::response(['data' => ['id' => 7]], 200),
        'unimus:8085/api/v2/devices/7/backups/latest' => Http::response([
            'data' => ['id' => 3, 'validSince' => 300, 'validUntil' => null, 'type' => 'TEXT', 'bytes' => base64_encode('latest config')],
        ], 200),
    ]);

    $latest = makeProvider()->latest(makeDevice());

    expect($latest['id'])->toBe('3');
    expect($latest['content'])->toBe('latest config');
});

test('refresh triggers backup job', function () {
    Http::fake([
        'unimus:8085/api/v2/devices/findByAddress/*' => Http::response(['data' => ['id' => 7]], 200),
        'unimus:8085/api/v2/jobs/backup*' => Http::response(['data' => ['accepted' => 1, 'refused' => 0]], 202),
    ]);

    expect(makeProvider()->refresh(makeDevice(), 'admin'))->toBeTrue();

    Http::assertSent(fn ($request) => str_contains((string) $request->url(), '/jobs/backup?id=7')
        && $request->method() === 'PATCH');
});

test('refresh returns false when backup refused', function () {
    Http::fake([
        'unimus:8085/api/v2/devices/findByAddress/*' => Http::response(['data' => ['id' => 7]], 200),
        'unimus:8085/api/v2/jobs/backup*' => Http::response(['data' => ['accepted' => 0, 'refused' => 1]], 202),
    ]);

    expect(makeProvider()->refresh(makeDevice(), 'admin'))->toBeFalse();
});

test('refresh returns false when device not found', function () {
    Http::fake(['unimus:8085/api/v2/devices/findByAddress/*' => Http::response(['code' => 404], 404)]);

    expect(makeProvider()->refresh(makeDevice(), 'admin'))->toBeFalse();
});

test('content rejects non numeric id', function () {
    Http::fake(['*' => Http::response(['data' => []], 200)]);

    $provider = makeProvider();

    expect($provider->content(makeDevice(), 'a1b2c3d'))->toBeNull();
    expect($provider->lastError())->toBe(ConfigBackupProvider::ERROR_BACKUP_NOT_FOUND);
    Http::assertNothingSent();
});

test('diff rejects non numeric ids', function () {
    Http::fake(['*' => Http::response(['data' => []], 200)]);

    $provider = makeProvider();

    expect($provider->diff(makeDevice(), 'abc', '2'))->toBeNull();
    expect($provider->lastError())->toBe(ConfigBackupProvider::ERROR_BACKUP_NOT_FOUND);
    Http::assertNothingSent();
});

test('device not found error passthrough', function () {
    Http::fake(['*' => Http::response(['code' => 404], 404)]);

    $provider = makeProvider();

    expect($provider->backups(makeDevice()))->toBeNull();
    expect($provider->lastError())->toBe(ConfigBackupProvider::ERROR_DEVICE_NOT_FOUND);
});

test('unreachable error passthrough', function () {
    Http::fake(function (): void {
        throw new ConnectionException('cURL error 7: Failed to connect');
    });

    Log::shouldReceive('warning')->atLeast()->once();

    $provider = makeProvider();

    expect($provider->backups(makeDevice()))->toBeNull();
    expect($provider->lastError())->toBe(ConfigBackupProvider::ERROR_UNREACHABLE);
});

test('last error resets between calls', function () {
    Http::fake([
        'unimus:8085/api/v2/devices/findByAddress/*' => Http::response(['data' => ['id' => 7]], 200),
        'unimus:8085/api/v2/devices/7/backups*' => Http::response([
            'data' => [['id' => 1, 'validSince' => 100, 'validUntil' => null, 'type' => 'TEXT', 'bytes' => base64_encode('config')]],
            'paginator' => ['totalCount' => 1, 'totalPages' => 1, 'page' => 0, 'size' => 50],
        ], 200),
    ]);

    $provider = makeProvider();

    expect($provider->content(makeDevice(), 'bogus'))->toBeNull();
    expect($provider->lastError())->not->toBeNull();

    expect($provider->backups(makeDevice()))->not->toBeNull();
    expect($provider->lastError())->toBeNull();
});
