<?php

/**
 * ApiV2TestCase.php
 *
 * Shared setup for the v2 API tests
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

namespace LibreNMS\Tests\Feature\Api\V2;

use App\Facades\LibrenmsConfig;
use App\Models\User;
use App\Providers\ApiPlatformServiceProvider;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use LibreNMS\Tests\DBTestCase;

abstract class ApiV2TestCase extends DBTestCase
{
    use DatabaseTransactions;

    /** @var array<int, string> */
    private array $tokens = [];

    private ?User $admin = null;

    protected function setUp(): void
    {
        // Before parent::setUp(), which is what creates the application: the
        // provider decides whether to register API Platform while it boots,
        // and skips it for the rest of the suite.
        ApiPlatformServiceProvider::$registerForTesting = true;

        parent::setUp();

        LibrenmsConfig::set('api.v2.enabled', true);
    }

    protected function tearDown(): void
    {
        ApiPlatformServiceProvider::$registerForTesting = false;

        parent::tearDown();
    }

    protected function admin(): User
    {
        return $this->admin ??= User::factory()->admin()->create();
    }

    /**
     * Request as the given user, authenticating the way a client has to.
     */
    protected function getJsonAs(User $user, string $uri, string $accept = 'application/json'): TestResponse
    {
        auth()->forgetGuards();

        $token = $this->tokens[$user->user_id] ??= $user->createToken('test')->plainTextToken;

        return $this->json('GET', $uri, [], [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => $accept,
        ]);
    }
}
