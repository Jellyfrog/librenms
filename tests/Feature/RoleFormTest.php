<?php

use App\Models\User;

uses(LibreNMS\Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

test('risky permissions are marked', function (): void {
    $user = User::factory()->admin()->create(['enabled' => true]);

    $this->actingAs($user)
        ->get(route('roles.create'))
        ->assertOk()
        ->assertSee(__('permissions.alert-template.update.label'))
        ->assertSee(__('permissions.rbac.risky'))
        ->assertSee(__('permissions.alert-template.update.risk'))
        ->assertSee(__('permissions.sensor.view.label'))
        ->assertDontSee('permissions.sensor');
});