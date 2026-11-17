<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');
});

/**
 * @param  array<int, string>  $parameters
 * @return TestResponse<\Illuminate\Http\Response>
 */
function getSettings(array $parameters, string $role = 'admin'): TestResponse
{
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole($role);

    return $this->actingAs($user)->get(route('settings', $parameters));
}

/**
 * @param  TestResponse<\Illuminate\Http\Response>  $response
 */
function assertActive(TestResponse $response, string $tab, string $section, string $setting): void
{
    $response->assertOk()
        ->assertViewHas('active_tab', $tab)
        ->assertViewHas('active_section', $section)
        ->assertViewHas('active_setting', $setting);
}

test('tab and section', function () {
    assertActive(getSettings(['poller', 'snmp']), 'poller', 'snmp', '');
});

test('setting only', function () {
    assertActive(getSettings(['snmp.max_repeaters']), 'poller', 'snmp', 'snmp.max_repeaters');
});

test('setting with tab and section', function () {
    assertActive(getSettings(['poller', 'snmp', 'snmp.max_repeaters']), 'poller', 'snmp', 'snmp.max_repeaters');
});

test('setting corrects wrong tab and section', function () {
    assertActive(getSettings(['alerting', 'general', 'snmp.max_repeaters']), 'poller', 'snmp', 'snmp.max_repeaters');
});

test('unknown setting is ignored', function () {
    assertActive(getSettings(['poller', 'snmp', 'not.a.setting']), 'poller', 'snmp', '');
});

test('user cannot view', function () {
    getSettings(['snmp.max_repeaters'], 'user')->assertForbidden();
});