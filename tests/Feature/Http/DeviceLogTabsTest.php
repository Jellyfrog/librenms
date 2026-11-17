<?php

use App\Models\Device;
use App\Models\User;
use Spatie\Permission\Models\Role;

uses(\LibreNMS\Tests\TestCase::class, \Illuminate\Foundation\Testing\DatabaseTransactions::class);

const DEVICE_LOG_TABS_PAYLOAD = '"></option></select><img src=x onerror=alert(1)>';

beforeEach(function (): void {
    Role::findOrCreate('admin');
});

/**
 * @return array<string, array{string, array<int, string>}>
 */
dataset('logTabs', function (): array {
    return [
        'syslog' => ['device.syslog', ['program', 'priority', 'from', 'to']],
        'eventlog' => ['device.eventlog', ['eventtype']],
        'graylog' => ['device.graylog', ['stream']],
    ];
});

/**
 * @param  array<int, string>  $params
 */
test('filter parameters are html escaped inside javascript', function (string $route, array $params): void {
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');
    $device = Device::factory()->create();

    $query = array_fill_keys($params, DEVICE_LOG_TABS_PAYLOAD);

    $response = $this->actingAs($admin)
        ->get(route($route, ['device' => $device] + $query))
        ->assertOk();

    $flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

    // values concatenated into HTML strings must be html escaped before being JS encoded
    $response->assertDontSee('+ ' . json_encode(DEVICE_LOG_TABS_PAYLOAD, $flags) . ' +', false);
    $response->assertSee('+ ' . json_encode(e(DEVICE_LOG_TABS_PAYLOAD), $flags) . ' +', false);
})->with('logTabs');

test('global graylog stream is escaped', function (): void {
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)
        ->get('/graylog?stream=' . urlencode(DEVICE_LOG_TABS_PAYLOAD))
        ->assertOk();

    $response->assertDontSee(DEVICE_LOG_TABS_PAYLOAD, false);
    $response->assertSee('+ ' . json_encode(htmlspecialchars(DEVICE_LOG_TABS_PAYLOAD)) . ' +', false);
});
