<?php

use App\Http\Controllers\Search\Data\HostLocation;
use App\Http\Controllers\Search\Data\LocationState;
use App\Models\Port;

uses(LibreNMS\Tests\TestCase::class);

test('only mac on the port is direct', function (): void {
    $location = new HostLocation('02aaaaaaaa01', 1, new Port, null, 1, null);

    expect($location->state())->toBe(LocationState::Direct);
    expect($location->state()->labelClass())->toBe('label-success');
});

test('several macs on the port is shared', function (): void {
    $location = new HostLocation('02aaaaaaaa01', 1, new Port, null, 3, null);

    expect($location->state())->toBe(LocationState::Shared);
    expect($location->state()->labelClass())->toBe('label-default');
});

test('aggregate is trunk even with one mac', function (): void {
    $location = new HostLocation('02aaaaaaaa01', 1, new Port(['ifType' => 'ieee8023adLag']), null, 1, null);

    expect($location->state())->toBe(LocationState::Trunk);
    expect($location->state()->labelClass())->toBe('label-warning');
});

test('uplink is trunk', function (): void {
    $port = (new Port)->setAttribute('links_count', 1);
    $location = new HostLocation('02aaaaaaaa01', 1, $port, null, 1, null);

    expect($location->isUplink())->toBeTrue();
    expect($location->state())->toBe(LocationState::Trunk);
});

test('unknown location is awarning', function (): void {
    expect(LocationState::Unknown->labelClass())->toBe('label-warning');
});
