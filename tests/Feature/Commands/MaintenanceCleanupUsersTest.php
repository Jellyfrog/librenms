<?php

/*
 * MaintenanceCleanupUsersTest.php
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    LibreNMS
 * @link       http://librenms.org
 * @copyright  2026 LibreNMS
 * @author     LibreNMS Contributors
 */

namespace LibreNMS\Tests\Feature\Commands;

use App\Facades\LibrenmsConfig;
use App\Models\ApiToken;
use App\Models\AuthLog;
use App\Models\User;
use Carbon\Carbon;
use LibreNMS\Tests\InMemoryDbTestCase;

final class MaintenanceCleanupUsersTest extends InMemoryDbTestCase
{
    public function testRadiusPurge(): void
    {
        LibrenmsConfig::set('auth_mechanism', 'radius');
        LibrenmsConfig::set('radius.users_purge', 30);

        $recent = $this->userWithAuthLog('recent', 'radius', Carbon::now()->subDays(2));
        $stale = $this->userWithAuthLog('stale', 'radius', Carbon::now()->subDays(60));
        $never = User::factory()->create(['username' => 'never', 'auth_type' => 'radius']);
        $other = User::factory()->create(['username' => 'other', 'auth_type' => 'mysql']);

        $this->artisan('maintenance:cleanup-users')->assertExitCode(0);

        $this->assertSame(
            [$recent->user_id, $other->user_id],
            User::orderBy('user_id')->pluck('user_id')->all(),
            'Only the user with a recent login and the user of another auth type should remain'
        );
        $this->assertNull(User::find($stale->user_id));
        $this->assertNull(User::find($never->user_id));
    }

    public function testUsersWithApiTokensAreKept(): void
    {
        LibrenmsConfig::set('auth_mechanism', 'radius');
        LibrenmsConfig::set('radius.users_purge', 30);

        $tokened = User::factory()->create(['username' => 'tokened', 'auth_type' => 'radius']);
        ApiToken::generateToken($tokened);
        $stale = $this->userWithAuthLog('stale', 'radius', Carbon::now()->subDays(60));

        $this->artisan('maintenance:cleanup-users')->assertExitCode(0);

        $this->assertSame([$tokened->user_id], User::pluck('user_id')->all());
        $this->assertNull(User::find($stale->user_id));
    }

    public function testUnknownAuthTypeUsersArePurged(): void
    {
        // scopeThisAuth() also matches users where the auth type is not known
        LibrenmsConfig::set('auth_mechanism', 'radius');
        LibrenmsConfig::set('radius.users_purge', 30);

        User::factory()->create(['username' => 'unknown', 'auth_type' => null]);
        User::factory()->create(['username' => 'empty', 'auth_type' => '']);

        $this->artisan('maintenance:cleanup-users')->assertExitCode(0);

        $this->assertSame(0, User::count());
    }

    public function testActiveDirectoryPurge(): void
    {
        LibrenmsConfig::set('auth_mechanism', 'active_directory');
        LibrenmsConfig::set('active_directory.users_purge', 10);
        LibrenmsConfig::set('radius.users_purge', 0); // must not be used

        $recent = $this->userWithAuthLog('recent', 'active_directory', Carbon::now()->subDays(5));
        $stale = $this->userWithAuthLog('stale', 'active_directory', Carbon::now()->subDays(11));

        $this->artisan('maintenance:cleanup-users')->assertExitCode(0);

        $this->assertSame([$recent->user_id], User::pluck('user_id')->all());
        $this->assertNull(User::find($stale->user_id));
    }

    public function testUnsupportedAuthMechanismDoesNothing(): void
    {
        LibrenmsConfig::set('auth_mechanism', 'mysql');
        LibrenmsConfig::set('radius.users_purge', 30);

        $user = $this->userWithAuthLog('stale', 'mysql', Carbon::now()->subDays(60));

        $this->artisan('maintenance:cleanup-users')->assertExitCode(0);

        $this->assertSame([$user->user_id], User::pluck('user_id')->all());
    }

    public function testZeroDaysSettingDoesNothing(): void
    {
        LibrenmsConfig::set('auth_mechanism', 'radius');
        LibrenmsConfig::set('radius.users_purge', 0);

        $user = $this->userWithAuthLog('stale', 'radius', Carbon::now()->subDays(60));

        $this->artisan('maintenance:cleanup-users')->assertExitCode(0);

        $this->assertSame([$user->user_id], User::pluck('user_id')->all());
    }

    public function testInvalidDaysSettingDoesNothing(): void
    {
        LibrenmsConfig::set('auth_mechanism', 'radius');
        LibrenmsConfig::set('radius.users_purge', 'invalid');

        $user = $this->userWithAuthLog('stale', 'radius', Carbon::now()->subDays(60));

        $this->artisan('maintenance:cleanup-users')->assertExitCode(0);

        $this->assertSame([$user->user_id], User::pluck('user_id')->all());
    }

    public function testDaysArgumentOverridesConfig(): void
    {
        LibrenmsConfig::set('auth_mechanism', 'radius');
        LibrenmsConfig::set('radius.users_purge', 0); // disabled, but the argument wins

        $recent = $this->userWithAuthLog('recent', 'radius', Carbon::now()->subDays(1));
        $stale = $this->userWithAuthLog('stale', 'radius', Carbon::now()->subDays(4));

        $this->artisan('maintenance:cleanup-users', ['days' => 3])->assertExitCode(0);

        $this->assertSame([$recent->user_id], User::pluck('user_id')->all());
        $this->assertNull(User::find($stale->user_id));
    }

    public function testInvalidDaysArgument(): void
    {
        LibrenmsConfig::set('auth_mechanism', 'radius');
        LibrenmsConfig::set('radius.users_purge', 30);

        $user = $this->userWithAuthLog('stale', 'radius', Carbon::now()->subDays(60));

        $this->artisan('maintenance:cleanup-users', ['days' => 'abc'])->assertExitCode(1);

        $this->assertSame([$user->user_id], User::pluck('user_id')->all());
    }

    private function userWithAuthLog(string $username, string $auth_type, Carbon $datetime): User
    {
        $user = User::factory()->create(['username' => $username, 'auth_type' => $auth_type]);
        AuthLog::factory()->create(['user' => $username, 'datetime' => $datetime]);

        return $user;
    }
}
