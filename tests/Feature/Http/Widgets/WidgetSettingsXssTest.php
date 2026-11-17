<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Models\Dashboard;
use App\Models\User;
use App\Models\UserWidget;
use PHPUnit\Framework\Attributes\DataProvider;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

private const string PAYLOAD = '1||alert(document.domain)';

/**
 * @return array<string, array{string}>
 */
dataset('hideNavigationWidgets', function () {
    return [
        'alerts' => ['alerts'],
        'alertlog' => ['alertlog'],
        'alertlog-stats' => ['alertlog-stats'],
        'eventlog' => ['eventlog'],
        'syslog' => ['syslog'],
        'graylog' => ['graylog'],
    ];
});

test('hide navigation renders as boolean', function (string $widget) {
    $html = renderWidget($widget, ['hidenavigation' => self::PAYLOAD]);

    $this->assertStringNotContainsString('alert(document.domain)', $html);
    $this->assertStringContainsString('navigation: false,', $html);
})->with('hideNavigationWidgets');

test('hide navigation default shows navigation', function (string $widget) {
    $this->assertStringContainsString('navigation: true,', renderWidget($widget, []));
})->with('hideNavigationWidgets');

test('string settings cannot escape js string', function () {
    // a trailing backslash would escape the closing quote of a hand quoted JS string
    $html = renderWidget('eventlog', [
        'message_filter' => 'a\\',
        'eventtype' => '</script><script>alert(1)//',
    ]);

    $this->assertStringContainsString('message: "a\\\\",', $html);
    $this->assertStringNotContainsString('</script><script>', $html);
    expect(substr_count($html, '</script>'))->toBe(1);
});

test('image widget drops javascript target url', function () {
    $html = renderWidget('generic-image', [
        'image_url' => 'https://example.com/image.png',
        'target_url' => "java\tscript:alert(document.domain)",
    ], ['dimensions' => ['x' => 100, 'y' => 100]]);

    $this->assertStringNotContainsString('alert(document.domain)', $html);
});

test('image widget keeps relative target url', function () {
    $html = renderWidget('generic-image', [
        'image_url' => 'https://example.com/image.png',
        'target_url' => 'device/1?a=b:c',
    ], ['dimensions' => ['x' => 100, 'y' => 100]]);

    $this->assertStringContainsString('href="device/1?a=b:c"', $html);
});

/**
 * Render a widget on another user's shared dashboard as an unprivileged viewer.
 *
 * @param  array<string, mixed>  $settings
 * @param  array<string, mixed>  $request
 */
function renderWidget(string $widget, array $settings, array $request = []): string
{
    $owner = User::factory()->create();
    $dashboard = Dashboard::create([
        'user_id' => $owner->user_id,
        'dashboard_name' => 'shared',
        'access' => 1,
    ]);
    $userWidget = UserWidget::create([
        'user_id' => $owner->user_id,
        'widget' => $widget,
        'col' => 0,
        'row' => 0,
        'size_x' => 4,
        'size_y' => 3,
        'title' => '',
        'refresh' => 60,
        'settings' => $settings,
        'dashboard_id' => $dashboard->dashboard_id,
    ]);

    $response = $this->actingAs(User::factory()->create(['enabled' => 1]))
        ->postJson("ajax/dash/$widget", ['id' => $userWidget->user_widget_id] + $request)
        ->assertOk()
        ->assertJsonPath('status', 'ok');

    return (string) $response->json('html');
}