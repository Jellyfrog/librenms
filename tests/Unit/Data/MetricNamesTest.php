<?php

use App\Data\TimeSeries\MetricIdentity;
use App\Facades\LibrenmsConfig;
use LibreNMS\Data\Store\MetricNames;

uses(\LibreNMS\Tests\TestCase::class)->group('datastores');

function processor(): MetricIdentity
{
    return new MetricIdentity('processor', ['hostname' => 'localhost', 'type' => 'hr', 'index' => 1]);
}

test('a cleaned up metric is stored under the names it had', function (): void {
    $legacy = new MetricNames()->storedAs(processor());

    expect($legacy)->not->toBeNull();
    expect($legacy->measurement)->toBe('processors');
    expect($legacy->tagsOf(processor()))->toBe(['processor_type' => 'hr', 'processor_index' => 1]);
    expect($legacy->rrdNameOf(processor()))->toBe(['processor', 'hr', '1']);
});

test('canonical metrics are stored as identified', function (): void {
    LibrenmsConfig::set('datastore.canonical_metrics', true);
    $names = new MetricNames;

    expect($names->storedAs(processor()))->toBeNull();
    expect($names->legacyOf(processor()))->not->toBeNull();
});

test('a metric that was always clean has no other names', function (): void {
    expect(new MetricNames()->storedAs(new MetricIdentity('vm.cpu', ['cluster' => 'proxmox-abc', 'id' => 100])))->toBeNull();
});
