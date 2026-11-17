<?php

uses(\LibreNMS\Tests\InMemoryDbTestCase::class);
use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\Device;
use App\Models\User;
use Spatie\Permission\Models\Role;


test('pages require permission', function () {
    $user = User::factory()->create(['enabled' => 1]);

    $this->actingAs($user)->get(route('faults'))->assertForbidden();
    $this->actingAs($user)->get(route('alert-log'))->assertForbidden();
    $this->actingAs($user)->get(route('ajax.select.alert-rule'))->assertForbidden();
});

test('filter fields follow permissions', function () {
    $admin = user('admin');
    foreach (['faults', 'alert-log'] as $page) {
        $keys = filterKeys($admin, $page);
        expect($keys)->toContain('device.location_id');
        expect($keys)->toContain('device.groups.id');
    }

    $user = user('user');
    foreach (['faults', 'alert-log'] as $page) {
        $keys = filterKeys($user, $page);
        expect($keys)->toContain('search');
        expect($keys)->toContain('rule_id');
    }
    $this->actingAs($user)->get(route('ajax.select.alert-rule'))->assertOk();
});

test('device fault pages', function () {
    $device = Device::factory()->create();
    $admin = user('admin');

    $this->actingAs($admin)->get(route('device.faults', $device->device_id))
        ->assertOk()
        ->assertViewHas('filterFields', fn ($fields) => ! in_array('device_id', array_column($fields, 'key')))
        ->assertSee("'device.faults'", false)
        ->assertSee('fa-bar-chart', false)
        ->assertSee(route('device.faults.stats', $device->device_id), false)
        ->assertSee("device_id: $device->device_id", false);

    $this->actingAs($admin)->get(route('device.faults.history', $device->device_id))
        ->assertOk()
        ->assertViewHas('filterFields', fn ($fields) => ! in_array('device.location_id', array_column($fields, 'key')))
        ->assertSee("'device.alert-log'", false)
        ->assertSee("device_id: $device->device_id", false);

    $this->actingAs(user('user'))->get(route('device.faults', $device->device_id))->assertForbidden();
});

test('legacy device alert urls redirect', function () {
    $id = Device::factory()->create()->device_id;
    $admin = user('admin');

    $this->actingAs($admin)->get("/device/$id/alerts")->assertRedirect(route('device.faults', $id));
    $this->actingAs($admin)->get("/device/$id/alerts/section=alert-log?filter[state][eq]=ok")
        ->assertRedirect(route('device.faults.history', ['device' => $id, 'filter' => ['state' => ['eq' => 'ok']]]));
});

test('rule picker only lists rules on accessible devices', function () {
    $device = Device::factory()->create();
    $visible = AlertRule::factory()->create();
    $hidden = AlertRule::factory()->create();
    Alert::factory()->create(['device_id' => $device->device_id, 'rule_id' => $visible->id]);
    Alert::factory()->create(['rule_id' => $hidden->id]);

    // on another device
    $user = user('user');
    $user->devicesOwned()->attach($device->device_id);

    expect(ruleIds($user))->toBe([$visible->id]);
    expect(ruleIds($user, ['id' => $hidden->id]))->toBe([]);
    expect(ruleIds(user('admin')))->toEqualCanonicalizing([$visible->id, $hidden->id]);
});

test('legacy fault urls redirect to filters', function () {
    $admin = user('admin');

    $this->actingAs($admin)->get('/alerts/rule_id=3/acknowledged=0')
        ->assertRedirect(route('faults', ['filter' => ['rule_id' => ['eq' => 3], 'state' => ['neq' => 'acknowledged']]]));

    // an explicit state is kept, it already implies acknowledged/fired
    $this->actingAs($admin)->get('/alerts/state=3/acknowledged=0')
        ->assertRedirect(route('faults', ['filter' => ['state' => ['eq' => 'worse']]]));

    $this->actingAs($admin)->get('/alerts/fired=1/acknowledged=0')
        ->assertRedirect(route('faults', ['filter' => ['state' => ['eq' => 'alerted']]]));

    $this->actingAs($admin)->get('/alerts/alert_id=5')
        ->assertRedirect(route('faults', ['alert_id' => 5]));

    $this->actingAs($admin)->get('/alerts')->assertOk();
});

test('legacy alert log urls redirect to filters', function () {
    $admin = user('admin');

    $this->actingAs($admin)->get('/alert-log?device_id=5&state=0&severity[]=3&severity[]=9')
        ->assertRedirect(route('alert-log', ['filter' => [
            'device_id' => ['eq' => 5],
            'state' => ['eq' => 'ok'],
            'rule.severity' => ['in' => 'critical'],
        ]]));

    $this->actingAs($admin)->get('/alert-log?filter[state][in]=ok,alerted')
        ->assertOk()
        ->assertViewHas('filter', ['state' => ['in' => 'ok,alerted']]);
});

test('invalid filters are rejected', function () {
    $admin = user('admin');

    $this->actingAs($admin)->postJson(route('table.alerts'), ['filter' => ['search' => ['contains' => ['a']]]])
        ->assertUnprocessable();
    $this->actingAs($admin)->postJson(route('table.alertlog'), ['filter' => ['state' => ['bogus' => '1']]])
        ->assertUnprocessable();
});

function user(string $role): User
{
    Role::findOrCreate($role);
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole($role);

    return $user;
}

/**
 * @param  array<string, int>  $params
 * @return int[]
 */
function ruleIds(User $user, array $params = []): array
{
    return array_column($this->actingAs($user)->getJson(route('ajax.select.alert-rule', $params))->assertOk()->json('results'), 'id');
}

/**
 * @return string[]
 */
function filterKeys(User $user, string $route): array
{
    $response = $this->actingAs($user)->get(route($route));
    $response->assertOk();

    return array_column($response->viewData('filterFields'), 'key');
}
