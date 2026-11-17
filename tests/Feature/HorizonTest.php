<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Jobs\DispatchPollingWork;
use App\Jobs\PollDevice;
use App\Models\User;
use Laravel\Horizon\JobPayload;
use LibreNMS\Util\ModuleList;
use Spatie\Permission\Models\Permission;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('admin can view dashboard', function () {
    config(['queue.default' => 'redis']);
    $user = User::factory()->admin()->create(['enabled' => true]);

    $this->actingAs($user)->get('/horizon')->assertOk();
});

test('dashboard unavailable without redis', function () {
    config(['queue.default' => 'database']);
    $user = User::factory()->admin()->create(['enabled' => true]);

    $this->actingAs($user)->get('/horizon')->assertForbidden();
});

test('non admin cannot view dashboard', function () {
    $user = User::factory()->read()->create(['enabled' => true]);

    $this->actingAs($user)->get('/horizon')->assertForbidden();
});

test('horizon permission can view dashboard', function () {
    config(['queue.default' => 'redis']);
    Permission::findOrCreate('worker.horizon');
    $user = User::factory()->create(['enabled' => true]);
    $user->givePermissionTo('worker.horizon');

    $this->actingAs($user)->get('/horizon')->assertOk();
});

test('queue permissions do not grant dashboard', function () {
    config(['queue.default' => 'redis']);
    Permission::findOrCreate('worker.view');
    Permission::findOrCreate('worker.update');
    $user = User::factory()->create(['enabled' => true]);
    $user->givePermissionTo('worker.view', 'worker.update');

    $this->actingAs($user)->get('/horizon')->assertForbidden();
});

test('non admin cannot view dashboard in local environment', function () {
    $this->app['env'] = 'local';
    $user = User::factory()->read()->create(['enabled' => true]);

    $this->actingAs($user)->get('/horizon')->assertForbidden();
});

test('guest is redirected to login', function () {
    $this->get('/horizon')->assertRedirect();
});

test('dispatcher is silenced', function () {
    $payload = new JobPayload('{}')->prepare(new DispatchPollingWork(poll: false, discover: false));

    expect($payload->isSilenced())->toBeTrue();
});

test('poll device is tagged and not silenced', function () {
    $payload = new JobPayload('{}')->prepare(new PollDevice(42, ModuleList::fromUserOverrides([])));

    expect($payload->isSilenced())->toBeFalse();
    expect($payload->tags())->toBe(['poll', 'device:42']);
});

test('config is not loaded before the app has booted', function () {
    $app = require base_path('bootstrap/app.php');
    $loadedEarly = null;

    // runs before booted callbacks registered by providers, database settings are not available before this
    $app->booted(function ($app) use (&$loadedEarly): void {
        $loadedEarly = $app->resolved('librenms-config');
    });

    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    expect($loadedEarly)->toBeFalse('LibreNMS config was loaded during boot, it would be cached without database settings');
});