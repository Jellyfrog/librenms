<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Data\TimeSeries\MetricCollector;
use App\Data\TimeSeries\MetricIdentity;
use LibreNMS\Interfaces\Models\HasMetrics;

test('records the identity of the model', function () {
    $collector = new MetricCollector(['vm.cpu']);

    $collector->record(model(), 'vm.cpu', ['usage' => 25]);

    $entries = $collector->entries();
    expect($entries)->toHaveCount(1);
    expect($entries[0]->identity->measurement)->toBe('vm.cpu');
    expect($entries[0]->fields)->toBe(['usage' => 25]);
});

test('rejects measurements that are not allowed', function () {
    $this->expectException(\RuntimeException::class);

    new MetricCollector(['vm.cpu'])->record(model(), 'vm.unknown', ['usage' => 25]);
});

function model(): HasMetrics
{
    return new class implements HasMetrics
    {
        function metricIdentity(string $measurement): MetricIdentity
        {
            return new MetricIdentity($measurement, ['hostname' => 'host.example.com']);
        }
    };
}
