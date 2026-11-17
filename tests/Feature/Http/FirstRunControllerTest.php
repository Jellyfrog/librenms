<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Facades\LibrenmsConfig;
use App\Http\Controllers\FirstRunController;
use App\Models\Device;
use App\Models\Secret;
use App\Models\User;
use LibreNMS\Enum\SecretType;
use LibreNMS\Scan\LocalNetworks;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    Role::findOrCreate('admin');
    $this->admin = User::factory()->admin()->create(['enabled' => 1]);
    Device::query()->delete();
    LibrenmsConfig::set('first_run_complete', false);
});

test('dashboard redirects to first run without devices', function () {
    $this->actingAs($this->admin)->get(route('home'))
        ->assertRedirect(route('first-run'));
});

test('first run is not shown once set up', function () {
    expect(FirstRunController::shouldShow($this->admin))->toBeTrue();

    Device::factory()->create();
    expect(FirstRunController::shouldShow($this->admin))->toBeFalse('there are devices');

    Device::query()->delete();
    LibrenmsConfig::set('first_run_complete', true);
    expect(FirstRunController::shouldShow($this->admin))->toBeFalse('the first run is complete');
});

test('first run is not shown to users that can not set up', function () {
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole(Role::findOrCreate('user'));

    expect(FirstRunController::shouldShow($user))->toBeFalse();
    $this->actingAs($user)->get(route('first-run'))->assertForbidden();
});

test('first run needs permission to create secrets', function () {
    $user = userWith(['settings.update', 'device.create']);

    expect(FirstRunController::shouldShow($user))->toBeFalse('the credentials could not be saved');
    $this->actingAs($user)->get(route('first-run'))->assertForbidden();

    $user->givePermissionTo(Permission::findOrCreate('secret.create'));
    expect(FirstRunController::shouldShow($user->fresh()))->toBeTrue();
});

test('communities are only shown to users that can unmask secrets', function () {
    $secret = Secret::create(['description' => 'Office community', 'secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'hidden-community']]);
    LibrenmsConfig::set('snmp.default_credentials', [$secret->id]);
    $user = userWith(['settings.update', 'device.create', 'secret.create']);

    $this->actingAs($user)->get(route('first-run'))
        ->assertOk()
        ->assertDontSee('hidden-community')
        ->assertSee('Office community');

    $user->givePermissionTo(Permission::findOrCreate('secret.unmask'));
    $this->actingAs($user->fresh())->get(route('first-run'))
        ->assertOk()
        ->assertSee('hidden-community');
});

test('index shows the saved networks', function () {
    LibrenmsConfig::set('nets', ['198.51.100.0/24']);

    $this->actingAs($this->admin)->get(route('first-run'))
        ->assertOk()
        ->assertSee('198.51.100.0');
});

test('index does not claim suggestions when there are none', function () {
    LibrenmsConfig::set('nets', []);
    $this->app->instance(LocalNetworks::class, new LocalNetworks(containerMarkers: [__FILE__]));

    $this->actingAs($this->admin)->get(route('first-run'))
        ->assertOk()
        ->assertViewHas('config.networks', [])
        ->assertViewHas('config.networks_suggested', false);
});

test('index lists the default credentials in order', function () {
    $v3 = Secret::create(['description' => 'First v3', 'secret_type' => SecretType::SnmpV3, 'data' => ['authname' => 'first-v3', 'authpass' => 'never-sent']]);
    $community = Secret::create(['description' => 'Second community', 'secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'second-community']]);
    LibrenmsConfig::set('snmp.default_credentials', [$v3->id, $community->id]);

    $response = $this->actingAs($this->admin)->get(route('first-run'))->assertOk();

    expect($response->getContent())->toMatch('/first-v3.*second-community/s');
    $response->assertDontSee('never-sent');
});

test('credentials are saved in the order given', function () {
    $existingCommunity = Secret::create(['description' => 'Existing community', 'secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'existing-community']]);
    $kept = Secret::create(['description' => 'Existing v3', 'secret_type' => SecretType::SnmpV3, 'data' => ['authname' => 'existing']]);
    $removed = Secret::create(['description' => 'Removed v3', 'secret_type' => SecretType::SnmpV3, 'data' => ['authname' => 'removed']]);
    LibrenmsConfig::set('snmp.default_credentials', [$removed->id, $kept->id]);

    $response = $this->actingAs($this->admin)->postJson(route('first-run.credentials'), [
        'credentials' => [
            ['type' => 'v3', 'authlevel' => 'authNoPriv', 'authname' => 'monitor', 'authpass' => 'secret-pass', 'authalgo' => 'SHA'],
            ['type' => 'community', 'community' => 'existing-community'],
            ['id' => $kept->id],
            ['type' => 'community', 'community' => 'new-community'],
        ],
    ])->assertOk();

    $ids = LibrenmsConfig::get('snmp.default_credentials');
    expect($ids)->toHaveCount(4);
    $added = Secret::findOrFail($ids[0]);
    expect($added->secret_type)->toBe(SecretType::SnmpV3);
    expect($added->data['authname'])->toBe('monitor');
    expect($added->data['authalgo'])->toBe('SHA');
    expect($ids[1])->toBe($existingCommunity->id, 'a community that is already a secret is reused');
    expect($ids[2])->toBe($kept->id);
    expect(Secret::findOrFail($ids[3])->data['community'])->toBe('new-community');
    expect(Secret::find($removed->id))->not->toBeNull('a removed default is kept as a secret');

    // the page lists the saved credentials, without SNMPv3 passwords
    $response->assertJsonPath('credentials.0.id', $ids[0])
        ->assertJsonPath('credentials.0.type', 'v3')
        ->assertJsonPath('credentials.1.community', 'existing-community')
        ->assertJsonMissingPath('credentials.0.authpass');
});

test('credentials validates snmp v3', function () {
    $secrets = Secret::count();

    $this->actingAs($this->admin)->postJson(route('first-run.credentials'), [
        'credentials' => [
            ['type' => 'community', 'community' => 'public'],
            ['type' => 'v3', 'authlevel' => 'authPriv', 'authname' => 'monitor', 'authpass' => 'short'],
        ],
    ])->assertUnprocessable()->assertJsonValidationErrors(['credentials.1.authpass', 'credentials.1.cryptopass']);

    expect(Secret::count())->toBe($secrets, 'nothing is saved when a credential is invalid');
});

test('credentials requires acredential', function () {
    $this->actingAs($this->admin)->postJson(route('first-run.credentials'), ['credentials' => []])
        ->assertUnprocessable()->assertJsonValidationErrors('credentials');
});

test('networks are saved to the nets setting', function () {
    $this->actingAs($this->admin)->postJson(route('first-run.networks'), ['networks' => ['198.51.100.0/24', '203.0.113.0/25']])
        ->assertOk();

    expect(LibrenmsConfig::get('nets'))->toBe(['198.51.100.0/24', '203.0.113.0/25']);
});

test('networks rejects invalid and large networks', function () {
    $this->actingAs($this->admin)->postJson(route('first-run.networks'), ['networks' => ['192.0.2.1', 'not-a-network']])
        ->assertUnprocessable()->assertJsonValidationErrors(['networks.0', 'networks.1']);

    $this->actingAs($this->admin)->postJson(route('first-run.networks'), ['networks' => ['10.0.0.0/16']])
        ->assertUnprocessable()->assertJsonValidationErrors('networks');

    expect(LibrenmsConfig::get('nets'))->toBe([]);
});

test('scan needs the nets setting', function () {
    LibrenmsConfig::set('nets', []);

    $this->actingAs($this->admin)->postJson(route('first-run.scan'))
        ->assertUnprocessable()->assertJsonValidationErrors('networks');
});

test('finish completes the first run', function () {
    $this->actingAs($this->admin)->post(route('first-run.finish'), ['to' => 'devices'])
        ->assertRedirect(route('devices'));

    expect(LibrenmsConfig::get('first_run_complete'))->toBeTrue();
    $this->actingAs($this->admin)->get(route('home'))->assertOk();
});

/**
 * @param  string[]  $permissions
 */
function userWith(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }
    $user = User::factory()->create(['enabled' => 1]);
    $user->givePermissionTo($permissions);

    return $user;
}