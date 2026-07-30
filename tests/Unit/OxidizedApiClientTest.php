<?php

/**
 * OxidizedApiClientTest.php
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
 * @copyright  2024 LibreNMS
 */

use App\ApiClients\Oxidized;
use App\Facades\LibrenmsConfig;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(\LibreNMS\Tests\TestCase::class);

beforeEach(function () {
    LibrenmsConfig::set('oxidized.enabled', true);
    LibrenmsConfig::set('oxidized.url', 'http://oxidized:8888');
});

test('get content returns empty string on connection exception', function () {
    Http::fake(function (): void {
        throw new ConnectionException('cURL error 7: Failed to connect');
    });

    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn ($msg) => str_contains((string) $msg, 'Oxidized is not reachable'));

    $client = new Oxidized();
    $result = $client->getContent('/node/show/192.168.10.241?format=json');

    expect($result)->toBe('', 'getContent() should return an empty string when Oxidized is unreachable');
});

test('get content returns empty string when disabled', function () {
    LibrenmsConfig::set('oxidized.enabled', false);

    Http::fake(['*' => Http::response('{"name":"router"}', 200)]);

    $client = new Oxidized();
    $result = $client->getContent('/node/show/router?format=json');

    expect($result)->toBe('', 'getContent() should return an empty string when Oxidized is disabled');
    Http::assertNothingSent();
});

test('get content returns body on success', function () {
    $body = '{"name":"router","ip":"192.168.10.241","model":"ios"}';
    Http::fake(['*' => Http::response($body, 200)]);

    $client = new Oxidized();
    $result = $client->getContent('/node/show/router?format=json');

    expect($result)->toBe($body, 'getContent() should return the response body on success');
});

test('update node returns false on connection exception', function () {
    Http::fake(function (): void {
        throw new ConnectionException('cURL error 7: Failed to connect');
    });

    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn ($msg) => str_contains((string) $msg, 'Oxidized is not reachable'));

    $client = new Oxidized();
    $result = $client->updateNode('router', 'config changed', 'admin');

    expect($result)->toBeFalse('updateNode() should return false when Oxidized is unreachable');
});

test('reload nodes does not throw on connection exception', function () {
    LibrenmsConfig::set('oxidized.reload_nodes', true);

    Http::fake(function (): void {
        throw new ConnectionException('cURL error 7: Failed to connect');
    });

    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn ($msg) => str_contains((string) $msg, 'Oxidized is not reachable'));

    $client = new Oxidized();

    // Should not throw
    $client->reloadNodes();
});
