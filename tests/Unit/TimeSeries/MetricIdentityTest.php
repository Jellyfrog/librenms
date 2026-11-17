<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Data\TimeSeries\MetricIdentity;

test('it can be instantiated with measurement and tags', function () {
    $identity = new MetricIdentity('mempool', ['hostname' => 'host.example.com', 'mempool_index' => 10]);

    expect($identity->measurement)->toEqual('mempool');
    expect($identity->tags)->toEqual(['hostname' => 'host.example.com', 'mempool_index' => 10]);
});

test('a series is grouped by its hostname', function () {
    $identity = new MetricIdentity('processors', ['hostname' => 'host.example.com', 'type' => 'hr', 'index' => 1]);

    expect($identity->group())->toBe('host.example.com');
    expect($identity->tagsInGroup())->toBe(['type' => 'hr', 'index' => 1]);
});

test('a series without a hostname is grouped by its first tag', function () {
    $identity = new MetricIdentity('vm.cpu', ['cluster' => 'proxmox-abc', 'id' => 5]);

    expect($identity->group())->toBe('proxmox-abc');
    expect($identity->tagsInGroup())->toBe(['id' => 5]);
});

test('a series without tags has no group', function () {
    expect(new MetricIdentity('uptime')->group())->toBe('');
});
