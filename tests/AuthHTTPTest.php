<?php

/**
 * AuthHTTP.php
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://librenms.org
 *
 * @copyright  2017 Adam Bishop
 * @author     Adam Bishop <adam@omega.org.uk>
 */

use App\Facades\LibrenmsConfig;
use LibreNMS\Authentication\LegacyAuth;

use function strip_tags as strip_tags1;

uses(\LibreNMS\Tests\TestCase::class);

beforeEach(function () {
    $this->original_auth_mech = LibrenmsConfig::get('auth_mechanism');
    LibrenmsConfig::set('auth_mechanism', 'http-auth');
    $this->server = $_SERVER;
});

afterEach(function () {
    LibrenmsConfig::set('auth_mechanism', $this->original_auth_mech);
    $_SERVER = $this->server;
});

test('capability functions', function () {
    $a = LegacyAuth::reset();

    expect($a->canUpdatePasswords())->toBeFalse();
    expect($a->canManageUsers())->toBeTrue();
    expect($a->canUpdateUsers())->toBeTrue();
    expect($a->authIsExternal())->toBeTrue();
});

test('old behaviour against current', function () {
    $old_username = null;
    $new_username = null;

    $users = ['steve',  '   steve', 'steve   ', '   steve   ', '    steve   ', '', 'CAT'];
    $vars = ['REMOTE_USER', 'PHP_AUTH_USER'];

    $a = LegacyAuth::reset();

    foreach ($vars as $v) {
        foreach ($users as $u) {
            $_SERVER[$v] = $u;

            // Old Behaviour
            if (isset($_SERVER['REMOTE_USER'])) {
                $old_username = strip_tags1((string) $_SERVER['REMOTE_USER']);
            } elseif (isset($_SERVER['PHP_AUTH_USER']) && LibrenmsConfig::get('auth_mechanism') === 'http-auth') {
                $old_username = strip_tags((string) $_SERVER['PHP_AUTH_USER']);
            }

            // Current Behaviour
            if ($a->authIsExternal()) {
                $new_username = $a->getExternalUsername();
            }

            expect($old_username === null)->toBeFalse();
            expect($new_username === null)->toBeFalse();

            expect($old_username === $new_username)->toBeTrue();
        }

        unset($_SERVER[$v]);
    }
});