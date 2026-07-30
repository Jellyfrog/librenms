<?php

/**
 * AuthSSO.php
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
use App\Models\User;
use Illuminate\Support\Str;
use LibreNMS\Authentication\LegacyAuth;

uses(LibreNMS\Tests\DBTestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function (): void {
    $this->original_auth_mech = LibrenmsConfig::get('auth_mechanism');
    LibrenmsConfig::set('auth_mechanism', 'sso');

    $this->server = $_SERVER;
});

// Set up an SSO config for tests
function basicConfig()
{
    LibrenmsConfig::set('sso.mode', 'env');
    LibrenmsConfig::set('sso.create_users', true);
    LibrenmsConfig::set('sso.update_users', true);
    LibrenmsConfig::set('sso.trusted_proxies', ['127.0.0.1', '::1']);
    LibrenmsConfig::set('sso.user_attr', 'REMOTE_USER');
    LibrenmsConfig::set('sso.realname_attr', 'displayName');
    LibrenmsConfig::set('sso.email_attr', 'mail');
    LibrenmsConfig::set('sso.descr_attr', null);
    LibrenmsConfig::set('sso.level_attr', null);
    LibrenmsConfig::set('sso.group_strategy', 'static');
    LibrenmsConfig::set('sso.group_attr', 'member');
    LibrenmsConfig::set('sso.group_filter', '/(.*)/i');
    LibrenmsConfig::set('sso.group_delimiter', ';');
    LibrenmsConfig::set('sso.group_level_map', null);
    LibrenmsConfig::set('sso.static_level', -1);
}

// Set up $_SERVER in env mode
function basicEnvironmentEnv()
{
    unset($_SERVER);

    LibrenmsConfig::set('sso.mode', 'env');

    $_SERVER['REMOTE_ADDR'] = '::1';
    $_SERVER['REMOTE_USER'] = 'test';

    $_SERVER['mail'] = 'test@example.org';
    $_SERVER['displayName'] = Str::random();
}

// Set up $_SERVER in header mode
function basicEnvironmentHeader()
{
    unset($_SERVER);

    LibrenmsConfig::set('sso.mode', 'header');

    $_SERVER['REMOTE_ADDR'] = '::1';
    $_SERVER['REMOTE_USER'] = Str::random();

    $_SERVER['HTTP_MAIL'] = 'test@example.org';
    $_SERVER['HTTP_DISPLAYNAME'] = 'Test User';
}

function makeUser()
{
    $user = Str::random();
    $_SERVER['REMOTE_USER'] = $user;

    return $user;
}

test('valid auth no create update', function (): void {
    basicConfig();
    $a = LegacyAuth::reset();

    LibrenmsConfig::set('sso.create_users', false);
    LibrenmsConfig::set('sso.update_users', false);

    // Create a random username and store it with the defaults
    basicEnvironmentEnv();
    $user = makeUser();
    expect($a->authenticate(['username' => $user]))->toBeTrue();

    // Retrieve it and validate
    expect(User::thisAuth()->where('username', $user)->exists())->toBeFalse();
});

test('valid auth create only', function (): void {
    basicConfig();

    /** @var LibreNMS\Authentication\SSOAuthorizer */
    $a = LegacyAuth::reset();

    LibrenmsConfig::set('sso.create_users', true);
    LibrenmsConfig::set('sso.update_users', false);

    // Create a random username and store it with the defaults
    basicEnvironmentEnv();
    $user = makeUser();
    expect($a->authenticate(['username' => $user]))->toBeTrue();

    // Retrieve it and validate
    $dbuser = User::thisAuth()->where('username', $user)->firstOrNew();
    expect($a->authSSOGetAttr(LibrenmsConfig::get('sso.realname_attr')))->toBe($dbuser->realname);
    expect($dbuser->getRoleNames())->toBeEmpty();
    expect($a->authSSOGetAttr(LibrenmsConfig::get('sso.email_attr')))->toBe($dbuser->email);

    // Change a few things and reauth
    $_SERVER['mail'] = 'test@example.net';
    $_SERVER['displayName'] = 'Testier User';
    LibrenmsConfig::set('sso.static_level', 10);
    expect($a->authenticate(['username' => $user]))->toBeTrue();

    // Retrieve it and validate the update was not persisted
    $dbuser = User::thisAuth()->where('username', $user)->firstOrNew();
    expect($a->authSSOGetAttr(LibrenmsConfig::get('sso.realname_attr')) === $dbuser->realname)->toBeFalse();
    expect($dbuser->roles()->where('name', 'admin')->exists())->toBeFalse();
    expect($a->authSSOGetAttr(LibrenmsConfig::get('sso.email_attr')) === $dbuser->email)->toBeFalse();
});

test('valid auth update', function (): void {
    basicConfig();

    /** @var LibreNMS\Authentication\SSOAuthorizer */
    $a = LegacyAuth::reset();

    // Create a random username and store it with the defaults
    basicEnvironmentEnv();
    $user = makeUser();
    expect(auth()->attempt(['username' => $user]))->toBeTrue();

    // Change a few things and reauth
    $_SERVER['mail'] = 'test@example.net';
    $_SERVER['displayName'] = 'Testier User';
    LibrenmsConfig::set('sso.static_level', 10);
    expect(auth()->attempt(['username' => $user]))->toBeTrue();

    // Retrieve it and validate the update persisted
    $dbuser = User::thisAuth()->where('username', $user)->firstOrNew();
    expect($a->authSSOGetAttr(LibrenmsConfig::get('sso.realname_attr')))->toBe($dbuser->realname);
    expect($dbuser->roles()->where('name', 'admin')->exists())->toBeTrue();
    expect($a->authSSOGetAttr(LibrenmsConfig::get('sso.email_attr')))->toBe($dbuser->email);
});

test('bad auth', function (): void {
    basicConfig();

    /** @var LibreNMS\Authentication\SSOAuthorizer */
    $a = LegacyAuth::reset();

    basicEnvironmentEnv();
    unset($_SERVER);

    $this->expectException(LibreNMS\Exceptions\AuthenticationException::class);
    $a->authenticate([]);

    basicEnvironmentHeader();
    unset($_SERVER);

    $this->expectException(LibreNMS\Exceptions\AuthenticationException::class);
    $a->authenticate([]);
});

test('no attribute', function (): void {
    basicConfig();
    LegacyAuth::reset();

    basicEnvironmentEnv();
    unset($_SERVER['displayName']);
    unset($_SERVER['mail']);

    expect(auth()->attempt(['username' => makeUser()]))->toBeTrue();

    basicEnvironmentHeader();
    unset($_SERVER['HTTP_DISPLAYNAME']);
    unset($_SERVER['HTTP_MAIL']);

    expect(auth()->attempt(['username' => makeUser()]))->toBeTrue();
});

test('capability functions', function (): void {
    $a = LegacyAuth::reset();

    expect($a->canUpdatePasswords())->toBeFalse();
    expect($a->canManageUsers())->toBeTrue();
    expect($a->canUpdateUsers())->toBeTrue();
    expect($a->authIsExternal())->toBeTrue();
});

test('get external user name', function (): void {
    basicConfig();

    /** @var LibreNMS\Authentication\SSOAuthorizer */
    $a = LegacyAuth::reset();

    basicEnvironmentEnv();
    expect($a->getExternalUsername())->toBeString();

    // Missing
    unset($_SERVER['REMOTE_USER']);
    expect($a->getExternalUsername())->toBeNull();
    basicEnvironmentEnv();

    // Missing pointer to attribute
    LibrenmsConfig::forget('sso.user_attr');
    expect($a->getExternalUsername())->toBeNull();
    basicEnvironmentEnv();

    // Non-existant attribute
    LibrenmsConfig::set('sso.user_attr', 'foobar');
    expect($a->getExternalUsername())->toBeNull();
    basicEnvironmentEnv();

    // null pointer to attribute
    LibrenmsConfig::set('sso.user_attr', null);
    expect($a->getExternalUsername())->toBeNull();
    basicEnvironmentEnv();

    // null attribute
    LibrenmsConfig::set('sso.user_attr', 'REMOTE_USER');
    $_SERVER['REMOTE_USER'] = null;
    expect($a->getExternalUsername())->toBeNull();
});

test('get attr', function (): void {
    /** @var LibreNMS\Authentication\SSOAuthorizer */
    $a = LegacyAuth::reset();

    $_SERVER['HTTP_VALID_ATTR'] = 'string';
    $_SERVER['alsoVALID-ATTR'] = 'otherstring';

    LibrenmsConfig::set('sso.mode', 'env');
    expect($a->authSSOGetAttr('foobar'))->toBeNull();
    expect($a->authSSOGetAttr(null))->toBeNull();
    expect($a->authSSOGetAttr(''))->toBeNull();
    expect($a->authSSOGetAttr('alsoVALID-ATTR'))->toBeString();
    expect($a->authSSOGetAttr('HTTP_VALID_ATTR'))->toBeString();

    LibrenmsConfig::set('sso.mode', 'header');
    expect($a->authSSOGetAttr('foobar'))->toBeNull();
    expect($a->authSSOGetAttr(null))->toBeNull();
    expect($a->authSSOGetAttr(''))->toBeNull();
    expect($a->authSSOGetAttr('alsoVALID-ATTR'))->toBeNull();
    expect($a->authSSOGetAttr('VALID-ATTR'))->toBeString();
});

test('trusted proxies', function (): void {
    /** @var LibreNMS\Authentication\SSOAuthorizer */
    $a = LegacyAuth::reset();

    LibrenmsConfig::set('sso.trusted_proxies', ['127.0.0.1', '::1', '2001:630:50::/48', '8.8.8.0/25']);

    // v4 valid CIDR
    $_SERVER['REMOTE_ADDR'] = '8.8.8.8';
    expect($a->authSSOProxyTrusted())->toBeTrue();

    // v4 valid single
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    expect($a->authSSOProxyTrusted())->toBeTrue();

    // v4 invalid CIDR
    $_SERVER['REMOTE_ADDR'] = '9.8.8.8';
    expect($a->authSSOProxyTrusted())->toBeFalse();

    // v6 valid CIDR
    $_SERVER['REMOTE_ADDR'] = '2001:630:50:baad:beef:feed:face:cafe';
    expect($a->authSSOProxyTrusted())->toBeTrue();

    // v6 valid single
    $_SERVER['REMOTE_ADDR'] = '::1';
    expect($a->authSSOProxyTrusted())->toBeTrue();

    // v6 invalid CIDR
    $_SERVER['REMOTE_ADDR'] = '2600::';
    expect($a->authSSOProxyTrusted())->toBeFalse();

    // Not an IP
    $_SERVER['REMOTE_ADDR'] = 16;
    expect($a->authSSOProxyTrusted())->toBeFalse();

    //null
    $_SERVER['REMOTE_ADDR'] = null;
    expect($a->authSSOProxyTrusted())->toBeFalse();

    // Invalid String
    $_SERVER['REMOTE_ADDR'] = 'Not an IP address at all, but maybe PHP will end up type juggling somehow';
    expect($a->authSSOProxyTrusted())->toBeFalse();

    // Not a list
    LibrenmsConfig::set('sso.trusted_proxies', '8.8.8.0/25');
    $_SERVER['REMOTE_ADDR'] = '8.8.8.8';
    expect($a->authSSOProxyTrusted())->toBeFalse();

    // Unset
    unset($_SERVER['REMOTE_ADDR']);
    expect($a->authSSOProxyTrusted())->toBeFalse();
});

test('level caulculation from attr', function (): void {
    /** @var LibreNMS\Authentication\SSOAuthorizer $a */
    $a = LegacyAuth::reset();

    LibrenmsConfig::set('sso.mode', 'env');
    LibrenmsConfig::set('sso.group_strategy', 'attribute');

    //Integer
    LibrenmsConfig::set('sso.level_attr', 'level');
    $_SERVER['level'] = 5;
    expect($a->getRoles(''))->toBe(['global-read']);

    //String
    LibrenmsConfig::set('sso.level_attr', 'level');
    $_SERVER['level'] = '5';
    expect($a->getRoles(''))->toBe(['global-read']);

    // invalid level
    LibrenmsConfig::set('sso.level_attr', 'level');
    $_SERVER['level'] = 9;
    expect($a->getRoles(''))->toBe([]);

    //Invalid String
    LibrenmsConfig::set('sso.level_attr', 'level');
    $_SERVER['level'] = 'foobar';
    $this->expectException(LibreNMS\Exceptions\AuthenticationException::class);
    $a->getRoles('');

    //null
    LibrenmsConfig::set('sso.level_attr', 'level');
    $_SERVER['level'] = null;
    $this->expectException(LibreNMS\Exceptions\AuthenticationException::class);
    $a->getRoles('');

    //Unset pointer
    LibrenmsConfig::forget('sso.level_attr');
    $_SERVER['level'] = '9';
    $this->expectException(LibreNMS\Exceptions\AuthenticationException::class);
    $a->getRoles('');

    //Unset attr
    LibrenmsConfig::set('sso.level_attr', 'level');
    unset($_SERVER['level']);
    $this->expectException(LibreNMS\Exceptions\AuthenticationException::class);
    $a->getRoles('');
});

test('group parsing', function (): void {
    basicConfig();

    /** @var LibreNMS\Authentication\SSOAuthorizer */
    $a = LegacyAuth::reset();

    basicEnvironmentEnv();

    LibrenmsConfig::set('sso.static_level', 0);
    LibrenmsConfig::set('sso.group_strategy', 'map');
    LibrenmsConfig::set('sso.group_delimiter', ';');
    LibrenmsConfig::set('sso.group_attr', 'member');
    LibrenmsConfig::set('sso.group_level_map', ['librenms-admins' => 10, 'librenms-readers' => 1, 'librenms-billingcontacts' => 5]);
    $_SERVER['member'] = 'librenms-admins;librenms-readers;librenms-billingcontacts;unrelatedgroup;confluence-admins';

    // Valid options
    expect($a->authSSOParseGroups())->toBe(10);

    // No match
    $_SERVER['member'] = 'confluence-admins';
    expect($a->authSSOParseGroups())->toBe(0);

    // Delimiter only
    $_SERVER['member'] = ';;;;';
    expect($a->authSSOParseGroups())->toBe(0);

    // Empty
    $_SERVER['member'] = '';
    expect($a->authSSOParseGroups())->toBe(0);

    // Empty with default access level
    LibrenmsConfig::set('sso.static_level', 5);
    expect($a->authSSOParseGroups())->toBe(5);
    LibrenmsConfig::forget('sso.static_level');

    // Null
    $_SERVER['member'] = null;
    expect($a->authSSOParseGroups())->toBe(0);

    // Unset
    unset($_SERVER['member']);
    expect($a->authSSOParseGroups())->toBe(0);

    $_SERVER['member'] = 'librenms-admins;librenms-readers;librenms-billingcontacts;unrelatedgroup;confluence-admins';

    // Empty
    LibrenmsConfig::set('sso.group_level_map', []);
    expect($a->authSSOParseGroups())->toBe(0);

    // Not associative
    LibrenmsConfig::set('sso.group_level_map', ['foo', 'bar', 'librenms-admins']);
    expect($a->authSSOParseGroups())->toBe(0);

    // Null
    LibrenmsConfig::set('sso.group_level_map', null);
    expect($a->authSSOParseGroups())->toBe(0);

    // Unset
    LibrenmsConfig::forget('sso.group_level_map');
    expect($a->authSSOParseGroups())->toBe(0);

    // No delimiter
    LibrenmsConfig::forget('sso.group_delimiter');
    expect($a->authSSOParseGroups())->toBe(0);

    // Test group filtering by regex
    LibrenmsConfig::set('sso.group_filter', '/confluence-(.*)/i');
    LibrenmsConfig::set('sso.group_delimiter', ';');
    LibrenmsConfig::set('sso.group_level_map', ['librenms-admins' => 10, 'librenms-readers' => 1, 'librenms-billingcontacts' => 5, 'confluence-admins' => 7]);
    expect($a->authSSOParseGroups())->toBe(7);

    // Test group filtering by empty regex
    LibrenmsConfig::set('sso.group_filter', '');
    expect($a->authSSOParseGroups())->toBe(10);

    // Test group filtering by null regex
    LibrenmsConfig::set('sso.group_filter', null);
    expect($a->authSSOParseGroups())->toBe(10);
});

afterEach(function (): void {
    LibrenmsConfig::set('auth_mechanism', $this->original_auth_mech);
    LibrenmsConfig::forget('sso');
    $_SERVER = $this->server;
});
