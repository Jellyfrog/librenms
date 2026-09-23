<?php

/**
 * AuthLogAuthorizationTest.php
 *
 * Verifies that the authentication log (logins, logouts and failed login attempts of every user)
 * can only be listed by users holding the auth-log.view permission (admin, global-read or an
 * explicit grant), not by every user with the basic user role, including through the API.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2026 LibreNMS
 */

namespace LibreNMS\Tests\Feature;

use App\Models\AuthLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use LibreNMS\Tests\TestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class AuthLogAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('global-read');
        Role::findOrCreate('user');
        Permission::findOrCreate('auth-log.view');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['enabled' => 1]);
        $user->assignRole($role);

        return $user;
    }

    private function canViewAny(User $user): bool
    {
        return Gate::forUser($user)->allows('viewAny', AuthLog::class);
    }

    public function testUserRoleCannotViewAnyAuthLog(): void
    {
        $this->assertFalse($this->canViewAny($this->userWithRole('user')));
    }

    public function testAdminCanViewAnyAuthLog(): void
    {
        $this->assertTrue($this->canViewAny($this->userWithRole('admin')));
    }

    public function testGlobalReadCanViewAnyAuthLog(): void
    {
        $this->assertTrue($this->canViewAny($this->userWithRole('global-read')));
    }

    public function testUserRoleWithAuthLogPermissionCanViewAnyAuthLog(): void
    {
        $user = $this->userWithRole('user');
        $user->givePermissionTo('auth-log.view');

        $this->assertTrue($this->canViewAny($user));
    }

    public function testUserRoleCannotListAuthLogThroughApi(): void
    {
        AuthLog::factory()->count(2)->create();
        $token = $this->userWithRole('user')->createToken('test');

        $this->getJson('/api/v0/logs/authlog', ['X-Auth-Token' => $token->plainTextToken])
            ->assertForbidden();
    }

    public function testUserRoleWithAuthLogPermissionCanListAuthLogThroughApi(): void
    {
        AuthLog::factory()->count(2)->create();
        $user = $this->userWithRole('user');
        $user->givePermissionTo('auth-log.view');
        $token = $user->createToken('test');

        $this->getJson('/api/v0/logs/authlog', ['X-Auth-Token' => $token->plainTextToken])
            ->assertOk()
            ->assertJsonPath('total', 2);
    }

    public function testAdminCanListAuthLogThroughApi(): void
    {
        $logs = AuthLog::factory()->count(2)->create();
        $token = $this->userWithRole('admin')->createToken('test');

        $response = $this->getJson('/api/v0/logs/authlog', ['X-Auth-Token' => $token->plainTextToken])
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('total', 2)
            ->assertJsonCount(2, 'logs');

        $this->assertEqualsCanonicalizing($logs->pluck('user')->all(), $response->json('logs.*.user'));
    }
}
