<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('store generates hashed token with prefixed session plain', function () {
    $user = User::factory()->admin()->create(['enabled' => 1]);

    $response = $this->actingAs($user)->post(route('api-access.store'), [
        'description' => 'Test Token Description',
        'expires_in' => 30,
    ]);

    $response->assertRedirect(route('api-access.index'));
    $response->assertSessionHas('api_token_plain');

    $plainToken = session('api_token_plain');
    $this->assertStringContainsString('|', $plainToken);

    [$id, $secret] = explode('|', $plainToken, 2);

    $token = PersonalAccessToken::find($id);
    expect($token)->not->toBeNull();
    expect((int) $token->tokenable_id)->toBe($user->user_id);
    expect($token->name)->toBe('Test Token Description');
    expect($token->expires_at)->not->toBeNull();

    // Database stores the sha256 hash, not the plain secret or prefixed token
    expect($token->token)->toBe(hash('sha256', $secret));
    $this->assertNotSame($secret, $token->token);
    $this->assertNotSame($plainToken, $token->token);
});

test('reset generates new hashed token with prefixed session plain', function () {
    $user = User::factory()->admin()->create(['enabled' => 1]);
    $token = $user->createToken('Initial Description');
    $pat = $token->accessToken;
    $oldHash = $pat->token;

    $response = $this->actingAs($user)->post(route('api-access.reset', $pat->id));

    $response->assertRedirect(route('api-access.index'));
    $response->assertSessionHas('api_token_plain');

    $newPlainToken = session('api_token_plain');
    $this->assertStringContainsString('|', $newPlainToken);

    [$id, $newSecret] = explode('|', $newPlainToken, 2);

    $newToken = PersonalAccessToken::find($id);
    expect($newToken)->not->toBeNull();
    $this->assertNotSame($oldHash, $newToken->token);
    expect($newToken->token)->toBe(hash('sha256', $newSecret));
});

test('update modifies description and disabled', function () {
    $user = User::factory()->admin()->create(['enabled' => 1]);
    $token = $user->createToken('Old Name');
    $pat = $token->accessToken;

    $response = $this->actingAs($user)->patchJson(route('api-access.update', $pat->id), [
        'description' => 'New Name',
        'disabled' => true,
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'ok',
            'description' => 'New Name',
            'disabled' => true,
            'status_label' => 'danger',
        ]);

    $pat->refresh();
    expect($pat->name)->toBe('New Name');
    expect($pat->expires_at)->not->toBeNull();
    expect($pat->expires_at->isPast())->toBeTrue();
});

test('update modifies expiration days and clears expiration', function () {
    $user = User::factory()->admin()->create(['enabled' => 1]);
    $token = $user->createToken('Expiring Token');
    $pat = $token->accessToken;

    // Set expiration to 30 days
    $response = $this->actingAs($user)->patchJson(route('api-access.update', $pat->id), [
        'disabled' => false,
        'expires_in' => 30,
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'ok',
            'disabled' => false,
            'status_label' => 'info',
        ]);

    $pat->refresh();
    expect($pat->expires_at)->not->toBeNull();
    expect($pat->expires_at->isPast())->toBeFalse();

    // Clear expiration (never expires)
    $response = $this->actingAs($user)->patchJson(route('api-access.update', $pat->id), [
        'disabled' => false,
        'expires_in' => 0,
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'ok',
            'disabled' => false,
            'status_label' => 'success',
        ]);

    $pat->refresh();
    expect($pat->expires_at)->toBeNull();
});

test('destroy deletes token', function () {
    $user = User::factory()->admin()->create(['enabled' => 1]);
    $token = $user->createToken('To Delete');
    $pat = $token->accessToken;

    $response = $this->actingAs($user)->delete(route('api-access.destroy', $pat->id));

    $response->assertRedirect(route('api-access.index'));
    expect(PersonalAccessToken::find($pat->id))->toBeNull();
});