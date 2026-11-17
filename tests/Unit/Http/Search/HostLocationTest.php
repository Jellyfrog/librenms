<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Http\Controllers\Search\Data\HostLocation;
use App\Http\Controllers\Search\Data\LocationState;
use App\Models\Port;

test('only mac on the port is direct', function () {
    $location = new HostLocation('02aaaaaaaa01', 1, new Port, null, 1, null);

    expect($location->state())->toBe(LocationState::Direct);
    expect($location->state()->labelClass())->toBe('label-success');
});

test('several macs on the port is shared', function () {
    $location = new HostLocation('02aaaaaaaa01', 1, new Port, null, 3, null);

    expect($location->state())->toBe(LocationState::Shared);
    expect($location->state()->labelClass())->toBe('label-default');
});

test('aggregate is trunk even with one mac', function () {
    $location = new HostLocation('02aaaaaaaa01', 1, new Port(['ifType' => 'ieee8023adLag']), null, 1, null);

    expect($location->state())->toBe(LocationState::Trunk);
    expect($location->state()->labelClass())->toBe('label-warning');
});

test('uplink is trunk', function () {
    $port = (new Port)->setAttribute('links_count', 1);
    $location = new HostLocation('02aaaaaaaa01', 1, $port, null, 1, null);

    expect($location->isUplink())->toBeTrue();
    expect($location->state())->toBe(LocationState::Trunk);
});

test('unknown location is awarning', function () {
    expect(LocationState::Unknown->labelClass())->toBe('label-warning');
});
