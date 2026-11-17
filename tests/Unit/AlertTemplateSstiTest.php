<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\AlertTemplate;
use LibreNMS\Alert\Template;

test('default title does not execute blade in device display name', function () {
    $GLOBALS['__test_ssti_flag'] = false;

    $maliciousDisplay = 'Router1 {{ ($GLOBALS["__test_ssti_flag"] = true) ? "malicious" : "" }} & Switch';

    $data = [
        'state' => 1,
        'display' => $maliciousDisplay,
        'name' => 'Test Rule',
        'template' => (new AlertTemplate)->forceFill([
            'name' => 'Default Alert Template',
            'title' => '',
            'title_rec' => '',
            'template' => '{{ $alert->title }}',
        ]),
        'alert' => [
            'state' => 1,
            'display' => $maliciousDisplay,
            'name' => 'Test Rule',
        ],
    ];

    $tpl = new Template();
    $renderedTitle = $tpl->getTitle($data);

    expect($GLOBALS['__test_ssti_flag'])->toBeFalse('Blade expressions in device display string should not be evaluated');
    expect($renderedTitle)->toEqual('Alert for device ' . $maliciousDisplay . ' - Test Rule');

    unset($GLOBALS['__test_ssti_flag']);
});

test('custom template title renders safely', function () {
    $data = [
        'state' => 1,
        'display' => 'my-switch-01',
        'name' => 'Ping Latency',
        'template' => (new AlertTemplate)->forceFill([
            'name' => 'Custom Template',
            'title' => 'Custom Alert: {{ $alert->display }} - {{ $alert->name }}',
            'title_rec' => 'Recovered: {{ $alert->display }}',
            'template' => '{{ $alert->title }}',
        ]),
        'alert' => [
            'state' => 1,
            'display' => 'my-switch-01',
            'name' => 'Ping Latency',
        ],
    ];

    $tpl = new Template();
    $renderedTitle = $tpl->getTitle($data);

    expect($renderedTitle)->toEqual('Custom Alert: my-switch-01 - Ping Latency');
});

test('directives in device display are not evaluated', function () {
    $GLOBALS['__test_ssti_flag_directive'] = false;

    $maliciousDisplay = 'Router1 @php($GLOBALS["__test_ssti_flag_directive"] = true)';

    $data = [
        'state' => 1,
        'display' => $maliciousDisplay,
        'name' => 'Test Rule',
        'template' => (new AlertTemplate)->forceFill([
            'name' => 'Default Alert Template',
            'title' => '',
            'title_rec' => '',
            'template' => '{{ $alert->title }}',
        ]),
        'alert' => [
            'state' => 1,
            'display' => $maliciousDisplay,
            'name' => 'Test Rule',
        ],
    ];

    $tpl = new Template();
    $renderedTitle = $tpl->getTitle($data);

    expect($GLOBALS['__test_ssti_flag_directive'])->toBeFalse('Blade directives like @php in device display string should not be evaluated');
    expect($renderedTitle)->toEqual('Alert for device ' . $maliciousDisplay . ' - Test Rule');

    unset($GLOBALS['__test_ssti_flag_directive']);
});

test('custom template failure falls back to safe default title', function () {
    $data = [
        'state' => 1,
        'display' => 'router & switch',
        'name' => 'Test Rule',
        'template' => (new AlertTemplate)->forceFill([
            'name' => 'Broken Template',
            'title' => '{{ $alert->invalidMethod() }}',
            'template' => '{{ $alert->title }}',
        ]),
        'alert' => [
            'state' => 1,
            'display' => 'router & switch',
            'name' => 'Test Rule',
        ],
    ];

    $tpl = new Template();
    $renderedTitle = $tpl->getTitle($data);

    expect($renderedTitle)->toEqual('Alert for device router & switch - Test Rule');
});
