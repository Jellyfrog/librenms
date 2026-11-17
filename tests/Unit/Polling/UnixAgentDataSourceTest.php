<?php

use App\Models\Mempool;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use LibreNMS\Polling\Method\DataSources\UnixAgentDataSource;
use LibreNMS\Polling\Method\ProbeResult;

uses(LibreNMS\Tests\TestCase::class);

const UNIX_AGENT_OUTPUT = "<<<uptime>>>\n12345.67 999\n<<<mem>>>\nMemTotal:       16000000 kB\nMemFree:         4000000 kB\nMemAvailable:   10000000 kB\nBuffers:          500000 kB\nCached:          3000000 kB\nSwapTotal:       2000000 kB\nSwapFree:        1500000 kB\n";

test('without mem section there are no mempools', function (): void {
    foreach (["<<<uptime>>>\n12345.67 999\n", ''] as $output) {
        expect(unixAgentSource($output)->discoverMempools())->toHaveCount(0);
        expect(unixAgentSource($output)->pollMempools(new EloquentCollection([new Mempool])))->toHaveCount(0);
    }
});

test('discovers meminfo like ucd', function (): void {
    $mempools = unixAgentSource(UNIX_AGENT_OUTPUT)->discoverMempools()->keyBy('mempool_class');

    expect($mempools)->not->toBeNull();
    expect($mempools->keys()->all())->toBe(['system', 'swap', 'buffers', 'cached', 'available']);
    expect($mempools->pluck('mempool_type')->unique()->values()->all())->toBe(['unix-agent']);

    $system = $mempools['system'];
    expect($system->mempool_total)->toEqual(16000000 * 1024);
    expect($system->mempool_used)->toEqual(12000000 * 1024);
    expect($system->mempool_perc)->toEqual(75);

    expect($mempools['buffers']->mempool_used)->toEqual(500000 * 1024);
    expect($mempools['available']->mempool_used)->toEqual(6000000 * 1024);
    expect($mempools['swap']->mempool_perc)->toEqual(25);
});

test('skips missing fields', function (): void {
    $mempools = unixAgentSource("<<<mem>>>\nMemTotal: 1000 kB\nMemFree: 500 kB\n")->discoverMempools();

    expect($mempools->pluck('mempool_class')->all())->toBe(['system']);
});

test('poll updates usage', function (): void {
    $mempool = new Mempool(['mempool_index' => 1, 'mempool_type' => 'unix-agent', 'mempool_class' => 'system', 'mempool_precision' => 1024]);

    unixAgentSource(UNIX_AGENT_OUTPUT)->pollMempools(new EloquentCollection([$mempool]));

    expect($mempool->mempool_free)->toEqual(4000000 * 1024);
    expect($mempool->mempool_perc)->toEqual(75);
});

function unixAgentSource(string $output): UnixAgentDataSource
{
    return new UnixAgentDataSource(ProbeResult::success(['output' => $output]));
}
