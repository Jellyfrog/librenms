<?php

/**
 * UserPrefTest.php
 *
 * Tests for UserPref model
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
 * @copyright  2026 LibreNMS
 */

use App\Models\User;
use App\Models\UserPref;

uses(\LibreNMS\Tests\DBTestCase::class);
uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('set pref', function () {
    $user = User::factory()->create();
    $pref = UserPref::setPref($user, 'test_pref', 'test_value');

    expect($pref)->toBeInstanceOf(UserPref::class);
    expect($pref->user_id)->toEqual($user->user_id);
    expect($pref->pref)->toEqual('test_pref');
    expect($pref->value)->toEqual('test_value');
});

test('get pref', function () {
    $user = User::factory()->create();
    UserPref::setPref($user, 'test_pref', 'test_value');

    $value = UserPref::getPref($user, 'test_pref');

    expect($value)->toEqual('test_value');
});

test('get pref returns null when not set', function () {
    $user = User::factory()->create();
    $value = UserPref::getPref($user, 'nonexistent_pref');

    expect($value)->toBeNull();
});

test('get pref with loaded relation', function () {
    $user = User::factory()->create();
    UserPref::setPref($user, 'test_pref', 'test_value');

    $user->load('preferences');
    $value = UserPref::getPref($user, 'test_pref');

    expect($value)->toEqual('test_value');
});

test('forget pref', function () {
    $user = User::factory()->create();
    UserPref::setPref($user, 'test_pref', 'test_value');

    UserPref::forgetPref($user, 'test_pref');

    expect(UserPref::getPref($user, 'test_pref'))->toBeNull();
});

test('set pref updates existing pref', function () {
    $user = User::factory()->create();
    UserPref::setPref($user, 'test_pref', 'original');

    UserPref::setPref($user, 'test_pref', 'updated');

    expect(UserPref::getPref($user, 'test_pref'))->toEqual('updated');
    expect($user->preferences()->where('pref', 'test_pref')->count())->toEqual(1);
});

test('array value is json encoded', function () {
    $user = User::factory()->create();
    $arrayValue = ['key' => 'value', 'nested' => ['a', 'b']];

    UserPref::setPref($user, 'test_pref', $arrayValue);

    expect(UserPref::getPref($user, 'test_pref'))->toEqual($arrayValue);
});

test('multiple prefs per user', function () {
    $user = User::factory()->create();
    UserPref::setPref($user, 'pref_one', 'value_one');
    UserPref::setPref($user, 'pref_two', 'value_two');

    expect(UserPref::getPref($user, 'pref_one'))->toEqual('value_one');
    expect(UserPref::getPref($user, 'pref_two'))->toEqual('value_two');
});

test('user relationship', function () {
    $user = User::factory()->create();
    $pref = UserPref::setPref($user, 'test_pref', 'test_value');

    expect($pref->user->user_id)->toEqual($user->user_id);
});
