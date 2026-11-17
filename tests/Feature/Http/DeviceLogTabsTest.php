<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

private const string PAYLOAD = '"></option></select><img src=x onerror=alert(1)>';

beforeEach(function () {
    Role::findOrCreate('admin');
});

/**
 * @return array<string, array{string, array<int, string>}>
 */
dataset('logTabs', function () {
    return [
        'syslog' => ['device.syslog', ['program', 'priority', 'from', 'to']],
        'eventlog' => ['device.eventlog', ['eventtype']],
        'graylog' => ['device.graylog', ['stream']],
    ];
});

test('filter parameters are html escaped inside javascript', function (string $route, array $params) {
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');
    $device = Device::factory()->create();

    $query = array_fill_keys($params, self::PAYLOAD);

    $response = $this->actingAs($admin)
        ->get(route($route, ['device' => $device] + $query))
        ->assertOk();

    $flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

    // values concatenated into HTML strings must be html escaped before being JS encoded
    $response->assertDontSee('+ ' . json_encode(self::PAYLOAD, $flags) . ' +', false);
    $response->assertSee('+ ' . json_encode(e(self::PAYLOAD), $flags) . ' +', false);
})->with('logTabs');

test('global graylog stream is escaped', function () {
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)
        ->get('/graylog?stream=' . urlencode(self::PAYLOAD))
        ->assertOk();

    $response->assertDontSee(self::PAYLOAD, false);
    $response->assertSee('+ ' . json_encode(htmlspecialchars(self::PAYLOAD)) . ' +', false);
});