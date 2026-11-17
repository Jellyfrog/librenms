<?php

/**
 * ProxyTest.php
 *
 * Tests Util\Proxy classes
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 */

use App\Facades\LibrenmsConfig;
use LibreNMS\Util\Http;
use LibreNMS\Util\Version;

uses(LibreNMS\Tests\TestCase::class);

test('client agent is correct', function (): void {
    expect(Http::client()->getOptions()['headers']['User-Agent'])->toEqual('LibreNMS/' . Version::VERSION);
});

test('proxy is not set', function (): void {
    LibrenmsConfig::set('http_proxy', '');
    LibrenmsConfig::set('https_proxy', '');
    LibrenmsConfig::set('no_proxy', '');
    $client_options = Http::client()->getOptions();
    expect($client_options['proxy']['http'])->toBeEmpty();
    expect($client_options['proxy']['https'])->toBeEmpty();
    expect($client_options['proxy']['no'])->toBeEmpty();
});

test('proxy is set', function (): void {
    LibrenmsConfig::set('http_proxy', 'http://proxy:5000');
    LibrenmsConfig::set('https_proxy', 'tcp://proxy:5183');
    LibrenmsConfig::set('no_proxy', 'localhost,127.0.0.1,::1,.domain.com');
    $client_options = Http::client()->getOptions();
    expect($client_options['proxy']['http'])->toEqual('http://proxy:5000');
    expect($client_options['proxy']['https'])->toEqual('tcp://proxy:5183');
    expect($client_options['proxy']['no'])->toEqual([
        'localhost',
        '127.0.0.1',
        '::1',
        '.domain.com',
    ]);
});

test('proxy is set from env', function (): void {
    LibrenmsConfig::set('http_proxy', '');
    LibrenmsConfig::set('https_proxy', '');
    LibrenmsConfig::set('no_proxy', '');

    putenv('HTTP_PROXY=someproxy:3182');
    putenv('HTTPS_PROXY=https://someproxy:3182');
    putenv('NO_PROXY=.there.com');

    $client_options = Http::client()->getOptions();
    expect($client_options['proxy']['http'])->toEqual('someproxy:3182');
    expect($client_options['proxy']['https'])->toEqual('https://someproxy:3182');
    expect($client_options['proxy']['no'])->toEqual([
        '.there.com',
    ]);

    putenv('http_proxy=otherproxy:3182');
    putenv('https_proxy=otherproxy:3183');
    putenv('no_proxy=dontproxymebro');

    $client_options = Http::client()->getOptions();
    expect($client_options['proxy']['http'])->toEqual('otherproxy:3182');
    expect($client_options['proxy']['https'])->toEqual('otherproxy:3183');
    expect($client_options['proxy']['no'])->toEqual([
        'dontproxymebro',
    ]);
});
