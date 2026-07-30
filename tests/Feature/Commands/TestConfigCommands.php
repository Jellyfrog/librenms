<?php

/*
 * TestSetConfigCommand.php
 *
 * -Description-
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    LibreNMS
 * @link       http://librenms.org
 * @copyright  2021 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use App\Facades\LibrenmsConfig;

uses(\LibreNMS\Tests\InMemoryDbTestCase::class);

/**
 * @param  string  $setting
 * @param  mixed  $expected
 */
function assertCliSets(string $setting, $expected): void
{
    assertCliGets($setting, null);
    test()->artisan('config:set', ['setting' => $setting, 'value' => json_encode($expected)])->assertExitCode(0);
    assertCliGets($setting, $expected);
    test()->artisan('config:set', ['setting' => $setting])
        ->expectsQuestion(trans('commands.config:set.confirm', ['setting' => $setting]), true)
        ->assertExitCode(0);
    assertCliGets($setting, null);
}

/**
 * @param  string  $setting
 * @param  mixed  $expected
 */
function assertCliGets(string $setting, $expected): void
{
    test()->assertSame($expected, LibrenmsConfig::get($setting));

    $command = test()->artisan('config:get', ['setting' => $setting]);
    if ($expected === null) {
        $command->assertExitCode(1);

        return;
    }

    $command->assertExitCode(0)
        ->expectsOutput(is_string($expected) ? $expected : json_encode($expected, JSON_PRETTY_PRINT))
        ->assertExitCode(0);
}

test('setting', function () {
    // simple
    LibrenmsConfig::set('login_message', null);
    assertCliSets('login_message', 'hello');

    // nested
    LibrenmsConfig::forget('allow_entity_sensor.amperes');
    assertCliSets('allow_entity_sensor.amperes', 'false');

    // set inside
    assertCliGets('auth_ldap_groups.somegroup', null);
    $this->artisan('config:set', ['setting' => 'auth_ldap_groups.somegroup', 'value' => '{"roles": ["banana"]}'])->assertExitCode(0);
    assertCliGets('auth_ldap_groups.somegroup', ['roles' => ['banana']]);
    $this->artisan('config:set', ['setting' => 'auth_ldap_groups.somegroup'])
        ->expectsConfirmation(trans('commands.config:set.forget_from', ['path' => 'somegroup', 'parent' => 'auth_ldap_groups']), 'yes')
        ->assertExitCode(0);

    // test append
    $community = LibrenmsConfig::get('snmp.community');
    assertCliGets('snmp.community', $community);
    $community[] = 'extra_community';
    $this->artisan('config:set', ['setting' => 'snmp.community.+', 'value' => 'extra_community'])->assertExitCode(0);
    assertCliGets('snmp.community', $community);

    // os bool
    assertCliSets('os.ios.rfc1628_compat', true);

    // os array
    assertCliSets('os.netonix.bad_iftype', ['ethernet', 'psuedowire']);

    // os array append
    $this->artisan('config:set', ['setting' => 'os.netonix.bad_iftype', 'value' => '["ethernet","psuedowire"]'])->assertExitCode(0);
    expect(LibrenmsConfig::get('os.netonix.bad_iftype'))->toEqual(['ethernet', 'psuedowire']);
    $this->artisan('config:set', ['setting' => 'os.netonix.bad_iftype.+', 'value' => 'other'])->assertExitCode(0);
    assertCliGets('os.netonix.bad_iftype', ['ethernet', 'psuedowire', 'other']);

    // dump
    $this->artisan('config:get', ['--dump' => true])
        ->expectsOutput(LibrenmsConfig::toJson())
        ->assertExitCode(0);
});

test('invalid setting', function () {
    // non-existent setting
    $this->artisan('config:set', ['setting' => 'this_will_never_be.a.setting'])
        ->assertExitCode(2);

    // invalid type
    $this->artisan('config:set', ['setting' => 'alert_rule.default_operation_step_duration', 'value' => 'string', '--no-ansi' => true])
        ->expectsOutput(trans('settings.validate.integer', ['value' => '"string"']))
        ->assertExitCode(2);

    // non-existent os
    $this->artisan('config:set', ['setting' => 'os.someos.this_will_never_be.a.setting'])
        ->expectsOutput(trans('commands.config:set.errors.invalid_os', ['os' => 'someos']))
        ->assertExitCode(2);

    // non-existent os setting
    $this->artisan('config:set', ['setting' => 'os.ios.this_will_never_be.a.setting'])
        ->doesntExpectOutput(trans('commands.config:set.errors.invalid_os', ['os' => 'ios']))
        ->assertExitCode(2);

    // append to non-array
    LibrenmsConfig::set('login_message', 'blah');
    LibrenmsConfig::get('login_message');
    $this->artisan('config:set', ['setting' => 'login_message.+', 'value' => 'something', '--no-ansi' => true])
        ->expectsOutput(trans('commands.config:set.errors.append'))
        ->assertExitCode(2);
});
