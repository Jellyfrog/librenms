<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\View\SettingPresenter;

test('translates setting', function () {
    $presented = SettingPresenter::present([
        'name' => 'auth.socialite.redirect',
        'type' => 'boolean',
        'value' => true,
        'default' => false,
        'units' => 'seconds',
    ]);

    expect($presented['name'])->toBe('auth.socialite.redirect');
    expect($presented['description'])->toBe(__('settings.settings.auth.socialite.redirect.description'));
    expect($presented['help'])->toBe(__('settings.settings.auth.socialite.redirect.help'));
    expect($presented['units'])->toBe(__('settings.units.seconds'));
    expect($presented['value'])->toBeTrue();
    expect($presented['default'])->toBeFalse();
    expect($presented['overridden'])->toBeFalse();
});

test('falls back to name', function () {
    $presented = SettingPresenter::present([
        'name' => 'not.a.real.setting',
        'type' => 'text',
        'units' => 'parsecs',
    ]);

    expect($presented['description'])->toBe('not.a.real.setting');
    expect($presented['help'])->toBeNull();
    expect($presented['units'])->toBe('parsecs');
});

test('overridden adds readonly help', function () {
    $presented = SettingPresenter::present([
        'name' => 'not.a.real.setting',
        'type' => 'text',
        'overridden' => true,
    ]);

    expect($presented['overridden'])->toBeTrue();
    expect($presented['help'])->toBe(__('settings.readonly'));
});

test('select options are ordered list', function () {
    $presented = SettingPresenter::present([
        'name' => 'alert.scheduled_maintenance_default_behavior',
        'type' => 'select',
        'options' => [1 => 'Skip alerts', 2 => 'Mute alerts', 3 => 'Run alerts'],
    ]);

    expect($presented['options'])->toBe([
        ['value' => '1', 'text' => __('settings.settings.alert.scheduled_maintenance_default_behavior.options.1')],
        ['value' => '2', 'text' => __('settings.settings.alert.scheduled_maintenance_default_behavior.options.2')],
        ['value' => '3', 'text' => __('settings.settings.alert.scheduled_maintenance_default_behavior.options.3')],
    ]);
});

test('multiple options accept collections', function () {
    $presented = SettingPresenter::present([
        'name' => 'poller_groups',
        'type' => 'multiple',
        'options' => collect([0 => 'General', 5 => 'Remote']),
    ], 'poller.settings');

    expect($presented['options'])->toBe([
        ['value' => '0', 'text' => 'General'],
        ['value' => '5', 'text' => 'Remote'],
    ]);
    expect($presented['description'])->toBe(__('poller.settings.settings.poller_groups.description'));
});

test('other options pass through', function () {
    $presented = SettingPresenter::present([
        'name' => 'default_poller_group',
        'type' => 'select-dynamic',
        'options' => ['target' => 'poller-group'],
    ]);

    expect($presented['options'])->toBe(['target' => 'poller-group']);
});
