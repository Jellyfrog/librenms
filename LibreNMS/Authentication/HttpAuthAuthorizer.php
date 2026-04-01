<?php

namespace LibreNMS\Authentication;

use App\Facades\LibrenmsConfig;
use LibreNMS\Exceptions\AuthenticationException;

class HttpAuthAuthorizer extends MysqlAuthorizer
{
    /** @var mixed */
    protected static $HAS_AUTH_USERMANAGEMENT = true;
    /** @var mixed */
    protected static $CAN_UPDATE_USER = true;
    /** @var mixed */
    protected static $CAN_UPDATE_PASSWORDS = false;
    /** @var mixed */
    protected static $AUTH_IS_EXTERNAL = true;

    public function authenticate($credentials)
    {
        if (isset($credentials['username']) && $this->userExists($credentials['username'])) {
            return true;
        }

        throw new AuthenticationException('No matching user found and http_auth_guest is not set');
    }

    public function userExists($username, $throw_exception = false)
    {
        if (parent::userExists($username)) {
            return true;
        }

        if (LibrenmsConfig::get('http_auth_guest') && parent::userExists(LibrenmsConfig::get('http_auth_guest'))) {
            return true;
        }

        return false;
    }

    public function getUserid($username)
    {
        $user_id = parent::getUserid($username);

        if ($user_id) {
            return $user_id;
        }

        if (LibrenmsConfig::get('http_auth_guest')) {
            return parent::getUserid(LibrenmsConfig::get('http_auth_guest'));
        }

        return -1;
    }
}
