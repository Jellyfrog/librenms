<?php

use App\Facades\LibrenmsConfig;
use App\Models\User;
use App\Providers\LegacyUserProvider;
use LibreNMS\Authentication\AuthorizerBase;
use LibreNMS\Authentication\LegacyAuth;
use LibreNMS\Exceptions\AuthenticationException;

uses(LibreNMS\Tests\DBTestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function (): void {
    $this->originalMechanism = LibrenmsConfig::get('auth_mechanism');
    LibrenmsConfig::set('auth_mechanism', 'ldap');
    LibrenmsConfig::set('auth_fallback_local', true);
});

afterEach(function (): void {
    LibrenmsConfig::set('auth_mechanism', $this->originalMechanism);
    LibrenmsConfig::set('auth_fallback_local', false);
    LegacyAuth::reset();
});

test('local user logs in when the configured auth does not know them', function (): void {
    useAuthorizer(['bob' => 'dir-pass']);
    $local = localUser('admin', 'local-pass');
    $provider = new LegacyUserProvider();

    $found = $provider->retrieveByCredentials(['username' => 'admin', 'password' => 'local-pass']);
    expect($found?->getAuthIdentifier())->toBe($local->getAuthIdentifier());
    expect($provider->validateCredentials($found, ['username' => 'admin', 'password' => 'local-pass']))->toBeTrue();
    expect($provider->validateCredentials($found, ['username' => 'admin', 'password' => 'wrong']))->toBeFalse();

    $local->enabled = false;
    $local->save();
    expect($provider->validateCredentials($found, ['username' => 'admin', 'password' => 'local-pass']))->toBeFalse();

    LibrenmsConfig::set('auth_fallback_local', false);
    expect($provider->retrieveByCredentials(['username' => 'admin', 'password' => 'local-pass']))->toBeNull();
});

test('local user logs in when the configured auth is down', function (string $failing): void {
    useAuthorizer(['bob' => 'dir-pass'], failing: $failing);
    $local = localUser('admin', 'local-pass');
    $provider = new LegacyUserProvider();

    $found = $provider->retrieveByCredentials(['username' => 'admin', 'password' => 'local-pass']);
    expect($found?->getAuthIdentifier())->toBe($local->getAuthIdentifier());

    LibrenmsConfig::set('auth_fallback_local', false);
    $this->expectException(\ErrorException::class);
    $provider->retrieveByCredentials(['username' => 'admin', 'password' => 'local-pass']);
})->with('outages');

/**
 * @return array<string, array{string}>
 */
dataset('outages', function () {
    return [
        'user lookup fails' => ['lookup'],
        // like ldap: the lookup hides the error, then authenticate() hits the server
        'authenticate fails' => ['authenticate'],
    ];
});

test('external auth does not fall back to local users', function (): void {
    LibrenmsConfig::set('auth_mechanism', 'http-auth');
    useAuthorizer(['bob' => 'dir-pass'], external: true);
    localUser('admin', 'local-pass');

    expect(new LegacyUserProvider()->retrieveByCredentials(['username' => 'admin', 'password' => 'local-pass']))->toBeNull();
});

test('known user is not checked against the local password', function (): void {
    useAuthorizer(['bob' => 'dir-pass']);
    localUser('bob', 'local-pass');
    $provider = new LegacyUserProvider();

    $found = $provider->retrieveByCredentials(['username' => 'bob', 'password' => 'local-pass']);
    expect($found)->toBeInstanceOf(User::class);
    expect($found->auth_type)->toBe('ldap');
    expect($provider->validateCredentials($found, ['username' => 'bob', 'password' => 'local-pass']))->toBeFalse();
});

test('user created on login is preferred over the local user', function (): void {
    // like radius: the user is only known after a successful authenticate()
    LibrenmsConfig::set('auth_mechanism', 'radius');
    useAuthorizer(['bob' => 'radius-pass'], knownAfterLogin: true);
    localUser('bob', 'local-pass');

    $found = new LegacyUserProvider()->retrieveByCredentials(['username' => 'bob', 'password' => 'radius-pass']);
    expect($found)->toBeInstanceOf(User::class);
    expect($found->auth_type)->toBe('radius');
});

function localUser(string $username, string $password): User
{
    $user = User::factory()->create(['username' => $username, 'auth_type' => 'mysql']);
    $user->setPassword($password);
    $user->save();

    return $user;
}

/**
 * @param  array<string, string>  $users  username => password
 * @param  'lookup'|'authenticate'|null  $failing  which call errors like an unreachable server
 */
function useAuthorizer(array $users, ?string $failing = null, bool $knownAfterLogin = false, bool $external = false): void
{
    $authorizer = new class($users, $failing, $knownAfterLogin, $external) extends AuthorizerBase
    {
        /** @var array<string, bool> */
        private array $loggedIn = [];

        /**
         * @param  array<string, string>  $users
         */
        public function __construct(
            private readonly array $users,
            private readonly ?string $failing,
            private readonly bool $knownAfterLogin,
            private readonly bool $external,
        ) {
        }

        public function authIsExternal()
        {
            return $this->external;
        }

        /**
         * @param  array<string, mixed>  $credentials
         */
        public function authenticate($credentials)
        {
            if ($this->failing === 'authenticate') {
                throw new \ErrorException("ldap_bind(): Unable to bind to server: Can't contact LDAP server");
            }

            $username = $credentials['username'] ?? '';
            if (($this->users[$username] ?? null) !== ($credentials['password'] ?? null)) {
                throw new AuthenticationException();
            }
            $this->loggedIn[$username] = true;

            return true;
        }

        public function userExists($username, $throw_exception = false)
        {
            return isset($this->users[$username]);
        }

        public function getUserid($username)
        {
            if ($this->failing === 'lookup') {
                throw new \ErrorException("ldap_search(): Search: Can't contact LDAP server");
            }
            if ($this->failing === 'authenticate') {
                return -1;
            }

            return isset($this->users[$username]) && (! $this->knownAfterLogin || isset($this->loggedIn[$username])) ? $username : -1;
        }

        /**
         * @return array<string, mixed>|false
         */
        public function getUser($user_id)
        {
            return isset($this->users[$user_id]) ? ['username' => $user_id] : false;
        }
    };
    new \ReflectionProperty(LegacyAuth::class, '_instance')->setValue(null, $authorizer);
}