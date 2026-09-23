<?php

namespace LibreNMS\Tests\Feature\Http;

use App\Facades\LibrenmsConfig;
use App\Models\User;
use App\Models\UserPref;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LibreNMS\Tests\TestCase;

final class TwoFactorControllerTest extends TestCase
{
    use RefreshDatabase;

    // counter based key, with counter 1 the next valid codes are 634456, 613687, 064292
    private const KEY = '5P3FLXBX7NU3ZBFOTWZL2GL5MKFEWBOA';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        LibrenmsConfig::set('twofactor', true);
        $this->user = User::factory()->create(['enabled' => 1]);
    }

    private function twoFactorSettings(): array
    {
        return [
            'key' => self::KEY,
            'fails' => 0,
            'last' => 0,
            'counter' => 1,
        ];
    }

    public function testWrongCodeDuringEnrollmentDoesNotSaveKey(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['twofactoradd' => $this->twoFactorSettings()])
            ->post(route('2fa.verify'), ['twofactor' => '999999']);

        $response->assertRedirect(route('2fa.form'));
        $this->assertSame(__('Wrong Two-Factor Token.'), session('errors')->first());

        // the key is not confirmed yet, saving it would enable two-factor with a key the user may not have
        $this->assertDatabaseMissing('users_prefs', ['user_id' => $this->user->user_id, 'pref' => 'twofactor']);

        // the failure is counted against the pending key instead
        $response->assertSessionHas('twofactoradd.key', self::KEY);
        $response->assertSessionHas('twofactoradd.fails', 1);
    }

    public function testCorrectCodeDuringEnrollmentSavesKey(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['twofactoradd' => $this->twoFactorSettings()])
            ->post(route('2fa.verify'), ['twofactor' => '634456']);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionMissing('twofactoradd');
        $response->assertSessionHas('twofactor', true);

        $saved = UserPref::getPref($this->user, 'twofactor');
        $this->assertSame(self::KEY, $saved['key']);
        $this->assertSame(0, $saved['fails']);
    }

    public function testWrongCodeForEnrolledUserCountsFailure(): void
    {
        UserPref::setPref($this->user, 'twofactor', $this->twoFactorSettings());

        $response = $this->actingAs($this->user)
            ->post(route('2fa.verify'), ['twofactor' => '999999']);

        $response->assertRedirect(route('2fa.form'));
        $this->assertSame(__('Wrong Two-Factor Token.'), session('errors')->first());
        $response->assertSessionMissing('twofactoradd');

        $saved = UserPref::getPref($this->user, 'twofactor');
        $this->assertSame(self::KEY, $saved['key']);
        $this->assertSame(1, $saved['fails']);
        $this->assertGreaterThan(0, $saved['last']);
    }
}
