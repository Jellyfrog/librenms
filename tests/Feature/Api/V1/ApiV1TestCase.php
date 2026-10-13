<?php

namespace LibreNMS\Tests\Feature\Api\V1;

use App\Facades\LibrenmsConfig;
use App\Facades\Permissions;
use App\Models\Device;
use App\Models\User;
use App\Providers\ApiPlatformServiceProvider;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use LibreNMS\Tests\DBTestCase;

/**
 * Base for tests of the v1 API: loads API Platform, which is not loaded for
 * other tests, and enables the API.
 */
abstract class ApiV1TestCase extends DBTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        ApiPlatformServiceProvider::$forTesting = true;
        parent::setUp();

        LibrenmsConfig::set('api.v1.enabled', true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        ApiPlatformServiceProvider::$forTesting = false;
    }

    protected function admin(): User
    {
        /** @var User $user */
        $user = User::factory()->admin()->create();

        return $user;
    }

    protected function limitedUser(Device $device): User
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('user');
        $user->devicesOwned()->attach($device->device_id);
        Permissions::invalidateCache();

        return $user;
    }

    /**
     * As a browser that logged in to the web interface: a session, and its cookie.
     *
     * @param  array<string, mixed>  $session
     */
    protected function withWebSession(User $user, array $session = []): static
    {
        return $this->withSession([Auth::guard('web')->getName() => $user->getAuthIdentifier()] + $session)
            ->withUnencryptedCookie(config('session.cookie'), session()->getId())
            ->withCredentials(); // json() sends no cookies otherwise
    }

    /**
     * @return array<string, string>
     */
    protected function headers(User $user): array
    {
        return ['Authorization' => 'Bearer ' . $user->createToken('test')->plainTextToken];
    }

    /**
     * A plain JSON request, authenticated as the given user.
     *
     * @return TestResponse<\Symfony\Component\HttpFoundation\Response>
     */
    protected function getApi(string $uri, User $user): TestResponse
    {
        auth()->forgetGuards();

        return $this->json('GET', $uri, [], $this->headers($user));
    }
}
