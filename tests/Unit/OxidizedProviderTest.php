<?php

/**
 * OxidizedProviderTest.php
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
use App\ApiClients\Oxidized;
use App\ConfigBackup\Providers\OxidizedProvider;
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\DeviceAttrib;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use LibreNMS\Interfaces\ConfigBackupProvider;

beforeEach(function (): void {
    LibrenmsConfig::set('oxidized.enabled', true);
    LibrenmsConfig::set('oxidized.url', 'http://oxidized:8888');
});

function makeOxidizedProvider(): OxidizedProvider
{
    return new OxidizedProvider(new Oxidized());
}

function makeOxidizedDevice(): Device
{
    $device = new Device(['hostname' => 'router.example.com']);
    $device->device_id = 42;

    // avoid hitting the database for attrib lookups in supportsDevice()
    $device->setRelation('attribs', collect());

    return $device;
}

/**
 * @param  array<string, mixed>  $extra
 * @return array<string, mixed>
 */
function fakeNode(array $extra = []): array
{
    return array_merge([
        'name' => 'router.example.com',
        'full_name' => 'router.example.com',
        'ip' => '192.168.1.1',
        'group' => null,
        'model' => 'ios',
        'last' => ['status' => 'success'],
    ], $extra);
}

test('is configured delegates to client', function (): void {
    expect(OxidizedProvider::isConfigured())->toBeTrue();

    LibrenmsConfig::set('oxidized.enabled', false);
    expect(OxidizedProvider::isConfigured())->toBeFalse();
});

test('supports device honours ignore lists', function (): void {
    $device = makeOxidizedDevice();
    $device->os = 'ios';

    expect(makeOxidizedProvider()->supportsDevice($device))->toBeTrue();

    LibrenmsConfig::set('oxidized.ignore_os', ['ios']);
    expect(makeOxidizedProvider()->supportsDevice($device))->toBeFalse();
});

test('supports device honours disable attrib', function (): void {
    $device = makeOxidizedDevice();
    $device->setRelation('attribs', collect([
        new DeviceAttrib(['attrib_type' => 'override_Oxidized_disable', 'attrib_value' => 'true']),
    ]));

    expect(makeOxidizedProvider()->supportsDevice($device))->toBeFalse();
});

test('backups maps versions', function (): void {
    Http::fake([
        'oxidized:8888/node/show/*' => Http::response(fakeNode(), 200),
        'oxidized:8888/node/version?*' => Http::response([
            ['oid' => 'aaaa1111', 'date' => '2026-07-14 00:24:24 +0000', 'message' => 'update', 'author' => ['name' => 'username', 'email' => 'user@example.com']],
            ['oid' => 'bbbb2222', 'date' => '2026-07-13 00:21:17 +0000', 'message' => 'update', 'author' => ['name' => 'Oxidized', 'email' => 'oxidized@example.com']],
        ], 200),
    ]);

    $list = makeOxidizedProvider()->backups(makeOxidizedDevice());

    expect($list['backups'][0]['id'])->toBe('aaaa1111');
    expect($list['backups'][0]['date'])->toBe(strtotime('2026-07-14 00:24:24 +0000'));
    expect($list['backups'][0]['author'])->toBe('username');
    expect($list['backups'][1]['author'])->toBeNull();
    expect($list['backups'][0]['type'])->toBe('TEXT');
    expect($list['backups'][0]['content'])->toBeNull();
    expect($list['total'])->toBe(2);
    expect($list['totalPages'])->toBe(1);
});

test('backups maps versions with time field when date is missing', function (): void {
    Http::fake([
        'oxidized:8888/node/show/*' => Http::response(fakeNode(), 200),
        'oxidized:8888/node/version?*' => Http::response([
            ['oid' => 'aaaa1111', 'date' => null, 'time' => '2026-07-29 23:36:42 +0000', 'message' => 'update'],
            ['oid' => 'bbbb2222', 'time' => '2026-07-28 23:36:42 +0000', 'message' => 'update'],
            ['oid' => 'cccc3333', 'message' => 'update'],
        ], 200),
    ]);

    $list = makeOxidizedProvider()->backups(makeOxidizedDevice());

    expect($list['backups'][0]['date'])->toBe(strtotime('2026-07-29 23:36:42 +0000'));
    expect($list['backups'][1]['date'])->toBe(strtotime('2026-07-28 23:36:42 +0000'));
    expect($list['backups'][2]['date'])->toBeNull();
    expect($list['total'])->toBe(3);
});

test('backups maps versions prefers date over time', function (): void {
    Http::fake([
        'oxidized:8888/node/show/*' => Http::response(fakeNode(), 200),
        'oxidized:8888/node/version?*' => Http::response([
            ['oid' => 'aaaa1111', 'date' => '2026-07-14 00:24:24 +0000', 'time' => '2026-07-29 23:36:42 +0000', 'message' => 'update'],
            ['oid' => 'bbbb2222', 'date' => 'unknown', 'time' => '2026-07-28 23:36:42 +0000', 'message' => 'update'],
        ], 200),
    ]);

    $list = makeOxidizedProvider()->backups(makeOxidizedDevice());

    expect($list['backups'][0]['date'])->toBe(strtotime('2026-07-14 00:24:24 +0000'));
    expect($list['backups'][1]['date'])->toBe(strtotime('2026-07-28 23:36:42 +0000'));
});

test('latest fetches current config without version list', function (): void {
    Http::fake([
        'oxidized:8888/node/show/*' => Http::response(fakeNode(['group' => 'core', 'mtime' => '2026-07-14 00:24:24 +0000']), 200),
        'oxidized:8888/node/fetch/*' => Http::response('hostname router', 200),
    ]);

    $latest = makeOxidizedProvider()->latest(makeOxidizedDevice());

    expect($latest['id'])->toBe('current');
    expect($latest['content'])->toBe('hostname router');
    expect($latest['date'])->toBe(strtotime('2026-07-14 00:24:24 +0000'));
    Http::assertSent(fn ($request) => str_ends_with((string) $request->url(), '/node/fetch/core/router.example.com'));
    Http::assertNotSent(fn ($request) => str_contains((string) $request->url(), '/node/version'));
});

test('latest ignores unknown mtime', function (): void {
    Http::fake([
        'oxidized:8888/node/show/*' => Http::response(fakeNode(['mtime' => 'unknown']), 200),
        'oxidized:8888/node/fetch/*' => Http::response('hostname router', 200),
    ]);

    expect(makeOxidizedProvider()->latest(makeOxidizedDevice())['date'])->toBeNull();
});

test('latest fetch failure is an error', function (): void {
    Http::fake([
        'oxidized:8888/node/show/*' => Http::response(fakeNode(), 200),
        'oxidized:8888/node/fetch/*' => Http::response('node not found', 500),
    ]);

    $provider = makeOxidizedProvider();

    expect($provider->latest(makeOxidizedDevice()))->toBeNull();
    expect($provider->lastError())->toBe(ConfigBackupProvider::ERROR_NO_BACKUPS);
});

test('content fetches version', function (): void {
    Http::fake([
        'oxidized:8888/node/show/*' => Http::response(fakeNode(), 200),
        'oxidized:8888/node/version/view*' => Http::response('interface eth0', 200),
    ]);

    $content = makeOxidizedProvider()->content(makeOxidizedDevice(), 'aaaa1111');

    expect($content)->toBe('interface eth0');
});

test('refresh queues node in oxidized', function (): void {
    Http::fake([
        'oxidized:8888/node/show/*' => Http::response(fakeNode(['name' => 'router.example.com']), 200),
        'oxidized:8888/node/next/*' => Http::response('true', 200),
    ]);

    expect(makeOxidizedProvider()->refresh(makeOxidizedDevice(), 'admin'))->toBeTrue();

    Http::assertSent(fn ($request) => str_contains((string) $request->url(), '/node/next/router.example.com')
        && $request->method() === 'PUT');
});

test('refresh returns false when oxidized rejects', function (): void {
    Http::fake([
        'oxidized:8888/node/show/*' => Http::response(fakeNode(), 200),
        'oxidized:8888/node/next/*' => Http::response('nope', 500),
    ]);

    expect(makeOxidizedProvider()->refresh(makeOxidizedDevice(), 'admin'))->toBeFalse();
});

test('group is included in version and diff requests', function (): void {
    Http::fake([
        'oxidized:8888/node/show/*' => Http::response(fakeNode(['group' => 'core']), 200),
        'oxidized:8888/node/version/view*' => Http::response('interface eth0', 200),
        'oxidized:8888/node/version/diffs*' => Http::response("@@ -1 +1 @@\n-a\n+b\n", 200),
    ]);

    $provider = makeOxidizedProvider();
    $device = makeOxidizedDevice();

    $provider->content($device, 'aaaa1111');
    Http::assertSent(fn ($request) => str_contains((string) $request->url(), '/node/version/view')
        && str_contains((string) $request->url(), 'group=core'));

    $provider->diff($device, 'bbbb2222', 'aaaa1111');
    Http::assertSent(fn ($request) => str_contains((string) $request->url(), '/node/version/diffs')
        && str_contains((string) $request->url(), 'group=core'));
});

test('content rejects non hex id', function (): void {
    Http::fake(['*' => Http::response('nope', 200)]);

    $provider = makeOxidizedProvider();

    expect($provider->content(makeOxidizedDevice(), 'not-an-oid'))->toBeNull();
    expect($provider->lastError())->toBe(ConfigBackupProvider::ERROR_BACKUP_NOT_FOUND);
    Http::assertNothingSent();
});

test('diff rejects non hex ids', function (): void {
    Http::fake(['*' => Http::response('nope', 200)]);

    $provider = makeOxidizedProvider();

    expect($provider->diff(makeOxidizedDevice(), 'zzz', 'aaaa1111'))->toBeNull();
    expect($provider->lastError())->toBe(ConfigBackupProvider::ERROR_BACKUP_NOT_FOUND);
    Http::assertNothingSent();
});

test('diff parses unified diff into groups', function (): void {
    $diff = <<<'DIFF'
        diff --git a/router b/router
        index abc1234..def5678 100644
        --- a/router
        +++ b/router
        @@ -1,3 +1,3 @@
         hostname router
        -ntp server 1.1.1.1
        +ntp server 2.2.2.2
         end
        DIFF;

    Http::fake([
        'oxidized:8888/node/show/*' => Http::response(fakeNode(), 200),
        'oxidized:8888/node/version/diffs*' => Http::response($diff, 200),
    ]);

    $groups = makeOxidizedProvider()->diff(makeOxidizedDevice(), 'bbbb2222', 'aaaa1111');

    expect($groups[0]['type'])->toBe('COMMON');
    expect($groups[0]['original'][0]['text'])->toBe('hostname router');
    expect($groups[0]['original'][0]['line'])->toBe(1);

    expect($groups[1]['type'])->toBe('DELETED');
    expect($groups[1]['original'][0]['text'])->toBe('ntp server 1.1.1.1');
    expect($groups[1]['original'][0]['line'])->toBe(2);

    expect($groups[2]['type'])->toBe('INSERTED');
    expect($groups[2]['revised'][0]['text'])->toBe('ntp server 2.2.2.2');
    expect($groups[2]['revised'][0]['line'])->toBe(2);

    expect($groups[3]['type'])->toBe('COMMON');
    expect($groups[3]['original'][0]['text'])->toBe('end');
});

test('diff requests newer as oid older as oid2', function (): void {
    Http::fake([
        'oxidized:8888/node/show/*' => Http::response(fakeNode(), 200),
        'oxidized:8888/node/version/diffs*' => Http::response("@@ -1 +1 @@\n-old\n+new\n", 200),
    ]);

    // orig (older) = bbbb2222, rev (newer) = aaaa1111
    makeOxidizedProvider()->diff(makeOxidizedDevice(), 'bbbb2222', 'aaaa1111');

    Http::assertSent(fn ($request) => str_contains((string) $request->url(), 'oid=aaaa1111')
        && str_contains((string) $request->url(), 'oid2=bbbb2222'));
});

test('no versioning falls back to current config', function (): void {
    Http::fake([
        'oxidized:8888/node/show/*' => Http::response(fakeNode(), 200),
        'oxidized:8888/node/version?*' => Http::response('not found', 404),
        'oxidized:8888/node/fetch/*' => Http::response('current config', 200),
    ]);

    $provider = makeOxidizedProvider();
    $device = makeOxidizedDevice();

    $list = $provider->backups($device);
    expect($list['total'])->toBe(1);
    expect($list['backups'][0]['id'])->toBe('current');

    expect($provider->content($device, 'current'))->toBe('current config');
});

test('device not found error', function (): void {
    Http::fake(['oxidized:8888/node/show/*' => Http::response('not found', 404)]);

    $provider = makeOxidizedProvider();

    expect($provider->backups(makeOxidizedDevice()))->toBeNull();
    expect($provider->lastError())->toBe(ConfigBackupProvider::ERROR_DEVICE_NOT_FOUND);
});

test('unreachable error', function (): void {
    Http::fake(function (): void {
        throw new ConnectionException('cURL error 7: Failed to connect');
    });

    Log::shouldReceive('warning')->atLeast()->once();

    $provider = makeOxidizedProvider();

    expect($provider->backups(makeOxidizedDevice()))->toBeNull();
    expect($provider->lastError())->toBe(ConfigBackupProvider::ERROR_UNREACHABLE);
});
