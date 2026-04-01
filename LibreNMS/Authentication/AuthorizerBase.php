<?php

/**
 * AuthorizerBase.php
 *
 * authentication functions
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

namespace LibreNMS\Authentication;

use App\Facades\LibrenmsConfig;
use LibreNMS\Interfaces\Authentication\Authorizer;

abstract class AuthorizerBase implements Authorizer
{
    /** @var mixed */
    protected static $HAS_AUTH_USERMANAGEMENT = false;
    /** @var mixed */
    protected static $CAN_UPDATE_USER = false;
    /** @var mixed */
    protected static $CAN_UPDATE_PASSWORDS = false;
    /** @var mixed */
    protected static $AUTH_IS_EXTERNAL = false;

    public function canUpdatePasswords($username = '')
    {
        return static::$CAN_UPDATE_PASSWORDS;
    }

    public function canManageUsers()
    {
        return static::$HAS_AUTH_USERMANAGEMENT;
    }

    public function canUpdateUsers()
    {
        return static::$CAN_UPDATE_USER;
    }

    public function authIsExternal()
    {
        return static::$AUTH_IS_EXTERNAL;
    }

    public function getExternalUsername()
    {
        return $_SERVER[LibrenmsConfig::get('http_auth_header')] ?? $_SERVER['PHP_AUTH_USER'] ?? null;
    }

    public function getRoles(string $username): array|false
    {
        return false; // return false don't update roles by default
    }
}
