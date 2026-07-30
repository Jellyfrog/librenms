<?php

use App\Facades\LibrenmsConfig;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(LibreNMS\Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    // Setup basic roles
    Role::findOrCreate('admin');
    Role::findOrCreate('user');

    // Setup permissions
    Permission::findOrCreate('user.viewAny');
    Permission::findOrCreate('user.view');
    Permission::findOrCreate('user.create');
    Permission::findOrCreate('user.update');
    Permission::findOrCreate('user.delete');
    Permission::findOrCreate('role.update');

    Password::defaults(fn () => Password::min(8));

    LibrenmsConfig::set('auth_mechanism', 'mysql');
});

test('admin with create permission can create user', function (): void {
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');
    $admin->givePermissionTo('user.create');

    $response = $this->actingAs($admin)->post(route('users.store'), [
        'username' => 'newuser',
        'new_password' => 'password123',
        'new_password_confirmation' => 'password123',
        'realname' => 'New User',
        'email' => 'newuser@example.com',
    ]);

    $response->assertRedirect(route('users.index'));
    $this->assertDatabaseHas('users', ['username' => 'newuser']);
});

test('admin with update permission can update any user', function (): void {
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');
    $admin->givePermissionTo('user.update');

    $targetUser = User::factory()->create(['username' => 'target', 'enabled' => 1]);

    $response = $this->actingAs($admin)->put(route('users.update', $targetUser), [
        'realname' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);

    $response->assertRedirect();
    expect($targetUser->fresh()->realname)->toEqual('Updated Name');
});

test('user can update their own profile', function (): void {
    $user = User::factory()->create(['password' => Hash::make('old_password'), 'enabled' => 1]);
    $user->assignRole('user');

    $response = $this->actingAs($user)->put(route('users.update', $user), [
        'realname' => 'My New Name',
        'email' => 'me@example.com',
    ]);

    $response->assertRedirect();
    expect($user->fresh()->realname)->toEqual('My New Name');
});

test('user can change their own password with old password verification', function (): void {
    $user = User::factory()->create(['password' => Hash::make('old_password'), 'enabled' => 1]);
    $user->assignRole('user');

    // Without old password - should fail
    $response = $this->actingAs($user)->put(route('users.update', $user), [
        'new_password' => 'new_password123',
        'new_password_confirmation' => 'new_password123',
    ]);
    $response->assertSessionHasErrors('old_password');

    // With correct old password - should succeed
    $response = $this->actingAs($user)->put(route('users.update', $user), [
        'old_password' => 'old_password',
        'new_password' => 'new_password123',
        'new_password_confirmation' => 'new_password123',
    ]);
    $response->assertRedirect();
    expect(Hash::check('new_password123', $user->fresh()->password))->toBeTrue();
});

test('user cannot update other users', function (): void {
    $user = User::factory()->create(['enabled' => 1]);
    $otherUser = User::factory()->create(['realname' => 'Other User', 'enabled' => 1]);

    $response = $this->actingAs($user)->put(route('users.update', $otherUser), [
        'realname' => 'Hacked Name',
    ]);

    $response->assertForbidden();
    expect($otherUser->fresh()->realname)->toEqual('Other User');
});

test('user cannot update restricted fields on themselves', function (): void {
    $user = User::factory()->create([
        'enabled' => 1,
        'can_modify_passwd' => 1,
        'username' => 'testuser',
    ]);
    $user->assignRole('user');

    $response = $this->actingAs($user)->put(route('users.update', $user), [
        'realname' => 'Modified Name',
        'enabled' => 0,
        'can_modify_passwd' => 0,
        'roles' => ['admin'],
    ]);

    $response->assertRedirect();

    $user->refresh();
    expect($user->realname)->toEqual('Modified Name');
    expect((int) $user->enabled)->toEqual(1, 'User should NOT be able to change their own enabled status');
    expect((int) $user->can_modify_passwd)->toEqual(1, 'User should NOT be able to change their own password modification permission');
    expect($user->hasRole('admin'))->toBeFalse('User should NOT be able to assign themselves roles');
});

test('admin can update roles on other users', function (): void {
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');
    $admin->givePermissionTo('user.update');
    $admin->givePermissionTo('role.update');

    $targetUser = User::factory()->create(['username' => 'target']);
    expect($targetUser->hasRole('admin'))->toBeFalse();

    $response = $this->actingAs($admin)->put(route('users.update', $targetUser), [
        'roles' => ['admin'],
    ]);

    $response->assertRedirect();
    expect($targetUser->fresh()->hasRole('admin'))->toBeTrue();
});

test('admin can update restricted fields on other users', function (): void {
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');
    $admin->givePermissionTo('user.update');

    $targetUser = User::factory()->create([
        'enabled' => 1,
        'can_modify_passwd' => 1,
        'username' => 'target',
    ]);

    $response = $this->actingAs($admin)->put(route('users.update', $targetUser), [
        'enabled' => 0,
        'can_modify_passwd' => 0,
    ]);

    $response->assertRedirect();

    $targetUser->refresh();
    expect((int) $targetUser->enabled)->toEqual(0);
    expect((int) $targetUser->can_modify_passwd)->toEqual(0);
});

test('admin can uncheck restricted fields', function (): void {
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');
    $admin->givePermissionTo('user.update');

    $targetUser = User::factory()->create([
        'enabled' => 1,
        'can_modify_passwd' => 1,
        'username' => 'target',
    ]);

    // Sending without enabled/can_modify_passwd should uncheck them (as they are checkboxes)
    // when an admin is updating another user.
    $response = $this->actingAs($admin)->put(route('users.update', $targetUser), [
        'realname' => 'Name Changed',
    ]);

    $response->assertRedirect();
    $targetUser->refresh();
    expect((int) $targetUser->enabled)->toEqual(0);
    expect((int) $targetUser->can_modify_passwd)->toEqual(0);

    // To explicitly set them to false.
    $response = $this->actingAs($admin)->put(route('users.update', $targetUser), [
        'enabled' => 0,
        'can_modify_passwd' => 0,
    ]);

    $response->assertRedirect();
    $targetUser->refresh();
    expect((int) $targetUser->enabled)->toEqual(0);
    expect((int) $targetUser->can_modify_passwd)->toEqual(0);
});
