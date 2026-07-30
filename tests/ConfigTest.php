<?php

/**
 * ConfigTest.php
 *
 * Tests for App\Facades\Config
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
 *
 * @copyright  2017 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */
uses(LibreNMS\Tests\TestCase::class);
use App\ConfigRepository;
use App\Facades\LibrenmsConfig;

beforeEach(function (): void {
    $this->config = new ReflectionProperty(ConfigRepository::class, 'config');
});

test('get basic', function (): void {
    $dir = realpath(__DIR__ . '/..');
    expect(LibrenmsConfig::get('install_dir'))->toEqual($dir);
});

test('set basic', function (): void {
    $instance = $this->app->make('librenms-config');
    LibrenmsConfig::set('basics', 'first');
    expect($this->config->getValue($instance)['basics'])->toEqual('first');
});

test('get', function (): void {
    setConfig(function (&$config): void {
        $config['one']['two']['three'] = 'easy';
    });

    expect(LibrenmsConfig::get('one.two.three'))->toEqual('easy');
});

test('get device setting', function (): void {
    $device = ['set' => true, 'null' => null];
    setConfig(function (&$config): void {
        $config['null'] = 'notnull!';
        $config['noprefix'] = true;
        $config['prefix']['global'] = true;
    });

    expect(LibrenmsConfig::getDeviceSetting($device, 'unset'))->toBeNull('Non-existing settings should return null');
    expect(LibrenmsConfig::getDeviceSetting($device, 'set'))->toBeTrue('Could not get setting from device array');
    expect(LibrenmsConfig::getDeviceSetting($device, 'noprefix'))->toBeTrue('Failed to get setting from global config');
    expect(LibrenmsConfig::getDeviceSetting($device, 'null'))->toEqual('notnull!', 'Null variables should defer to the global setting');
    expect(LibrenmsConfig::getDeviceSetting($device, 'global', 'prefix'))->toBeTrue('Failed to get setting from global config with a prefix');
    expect(LibrenmsConfig::getDeviceSetting($device, 'something', 'else', 'default'))->toEqual('default', 'Failed to return the default argument');
});

test('get os setting', function (): void {
    setConfig(function (&$config): void {
        $config['os']['nullos']['fancy'] = true;
        $config['fallback'] = true;
    });

    expect(LibrenmsConfig::getOsSetting(null, 'unset'))->toBeNull('$os is null, should return null');
    expect(LibrenmsConfig::getOsSetting('nullos', 'unset'))->toBeNull('Non-existing settings should return null');
    expect(LibrenmsConfig::getOsSetting('nullos', 'unset', false))->toBeFalse('Non-existing settings should return $default');
    expect(LibrenmsConfig::getOsSetting('nullos', 'fancy'))->toBeTrue('Failed to get setting');
    expect(LibrenmsConfig::getOsSetting('nullos', 'fallback'))->toBeNull('Incorrectly loaded global setting');

    // load yaml
    expect(LibrenmsConfig::getOsSetting('ios', 'os'))->toBe('ios');
    expect(count(LibrenmsConfig::get('os')))->toBeGreaterThan(500, 'Not all OS were loaded from yaml');
});

test('get combined', function (): void {
    setConfig(function (&$config): void {
        $config['num'] = ['one', 'two'];
        $config['withprefix']['num'] = ['four', 'five'];
        $config['os']['nullos']['num'] = ['two', 'three'];
        $config['assoc'] = ['a' => 'same', 'b' => 'same'];
        $config['withprefix']['assoc'] = ['a' => 'prefix_same', 'd' => 'prefix_same'];
        $config['os']['nullos']['assoc'] = ['b' => 'different', 'c' => 'still same'];
        $config['os']['nullos']['osset'] = 'ossetting';
        $config['gset'] = 'fallbackone';
        $config['withprefix']['gset'] = 'fallbacktwo';
    });

    expect(LibrenmsConfig::getCombined('nullos', 'non-existent', '', ['default']))->toBe(['default'], 'Did not return default value on non-existent key');
    expect(LibrenmsConfig::getCombined('nullos', 'osset', '', ['default']))->toBe(['ossetting'], 'Did not return OS value when global value is not set');
    expect(LibrenmsConfig::getCombined('nullos', 'gset', '', ['default']))->toBe(['fallbackone'], 'Did not return global value when OS value is not set');
    expect(LibrenmsConfig::getCombined('nullos', 'non-existent', 'withprefix.', ['default']))->toBe(['default'], 'Did not return default value on non-existent key');
    expect(LibrenmsConfig::getCombined('nullos', 'osset', 'withprefix.', ['default']))->toBe(['ossetting'], 'Did not return OS value when global value is not set');
    expect(LibrenmsConfig::getCombined('nullos', 'gset', 'withprefix.', ['default']))->toBe(['fallbacktwo'], 'Did not return global value when OS value is not set');

    $combined = LibrenmsConfig::getCombined('nullos', 'num');
    sort($combined);
    expect($combined)->toEqual(['one', 'three', 'two']);

    $combined = LibrenmsConfig::getCombined('nullos', 'num', 'withprefix.');
    sort($combined);
    expect($combined)->toEqual(['five', 'four', 'three', 'two']);

    expect(LibrenmsConfig::getCombined('nullos', 'assoc'))->toBe(['a' => 'same', 'b' => 'different', 'c' => 'still same']);

    // should associative not ignore same values (d=>prefix_same)?  are associative arrays actually used?
    expect(LibrenmsConfig::getCombined('nullos', 'assoc', 'withprefix.'))->toBe(['a' => 'prefix_same', 'b' => 'different', 'c' => 'still same']);
});

test('set', function (): void {
    $instance = $this->app->make('librenms-config');
    LibrenmsConfig::set('you.and.me', "I'll be there");

    expect($this->config->getValue($instance)['you']['and']['me'])->toEqual("I'll be there");
});

test('set persist', function (): void {
    $this->dbSetUp();

    $key = 'testing.persist';

    $query = App\Models\Config::query()->where('config_name', $key);

    $query->delete();
    expect($query->exists())->toBeFalse("$key should not be set, clean database");
    LibrenmsConfig::persist($key, 'one');
    expect($query->value('config_value'))->toEqual('one');
    LibrenmsConfig::persist($key, 'two');
    expect($query->value('config_value'))->toEqual('two');

    $this->dbTearDown();
});

test('has', function (): void {
    LibrenmsConfig::set('long.key.setting', 'no one cares');
    LibrenmsConfig::set('null', null);

    expect(LibrenmsConfig::has('null'))->toBeFalse('Keys set to null do not count as existing');
    expect(LibrenmsConfig::has('long'))->toBeTrue('Top level key should exist');
    expect(LibrenmsConfig::has('long.key.setting'))->toBeTrue('Exact exists on value');
    expect(LibrenmsConfig::has('long.key.setting.nothing'))->toBeFalse('Non-existent child setting');

    expect(LibrenmsConfig::has('off.the.wall'))->toBeFalse('Non-existent key');
    expect(LibrenmsConfig::has('off.the'))->toBeFalse('Config:has() should not modify the config');
});

test('get non existent', function (): void {
    expect(LibrenmsConfig::get('There.is.no.way.this.is.a.key'))->toBeNull();
    expect(LibrenmsConfig::has('There.is.no'))->toBeFalse();
    // should not add kes when getting
});

test('get non existent nested', function (): void {
    expect(LibrenmsConfig::get('cheese.and.bologna'))->toBeNull();
});

test('get subtree', function (): void {
    LibrenmsConfig::set('words.top', 'August');
    LibrenmsConfig::set('words.mid', 'And Everything');
    LibrenmsConfig::set('words.bot', 'After');
    $expected = [
        'top' => 'August',
        'mid' => 'And Everything',
        'bot' => 'After',
    ];

    expect(LibrenmsConfig::get('words'))->toEqual($expected);
});

/**
 * Pass an anonymous function which will be passed the config variable to modify before it is set
 *
 * @param  callable  $function
 */
function setConfig($function)
{
    $instance = app()->make('librenms-config');
    $reflection = new ReflectionProperty(ConfigRepository::class, 'config');
    $config = $reflection->getValue($instance);
    $function($config);
    $reflection->setValue($instance, $config);
}

test('forget', function (): void {
    LibrenmsConfig::set('forget.me', 'now');
    expect(LibrenmsConfig::has('forget.me'))->toBeTrue();

    LibrenmsConfig::forget('forget.me');
    expect(LibrenmsConfig::has('forget.me'))->toBeFalse();
});

test('forget subtree', function (): void {
    LibrenmsConfig::set('forget.me.sub', 'yep');
    expect(LibrenmsConfig::has('forget.me.sub'))->toBeTrue();

    LibrenmsConfig::forget('forget.me');
    expect(LibrenmsConfig::has('forget.me.sub'))->toBeFalse();
});
