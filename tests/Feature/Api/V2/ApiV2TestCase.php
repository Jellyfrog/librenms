<?php

/**
 * ApiV2TestCase.php
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
 * @link       https://www.librenms.org
 */

namespace LibreNMS\Tests\Feature\Api\V2;

use App\Facades\LibrenmsConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use LibreNMS\Tests\DBTestCase;

abstract class ApiV2TestCase extends DBTestCase
{
    use DatabaseTransactions;

    /** @var array<int, string> plain text token per user */
    private array $tokens = [];

    protected function setUp(): void
    {
        parent::setUp();

        LibrenmsConfig::set('api.v2.enabled', true);
    }

    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /**
     * Request as the given user, authenticating the way a client has to: a
     * bearer token, one per user.
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
