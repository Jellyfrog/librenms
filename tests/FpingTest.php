<?php

/**
 * FpingTest.php
 *
 * -Description-
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2019 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use LibreNMS\Data\Source\Fping;
use LibreNMS\Data\Source\FpingResponse;
use Symfony\Component\Process\Process;


test('up ping', function () {
    $output = "192.168.1.3 : xmt/rcv/%loss = 3/3/0%, min/avg/max = 0.62/0.71/0.93\n";
    mockFpingProcess($output, 0);

    $actual = app()->make(Fping::class)->ping('192.168.1.3');

    expect($actual->success())->toBeTrue();
    expect($actual->host)->toEqual('192.168.1.3');
    expect($actual->transmitted)->toEqual(3);
    expect($actual->received)->toEqual(3);
    expect($actual->loss)->toEqual(0);
    expect($actual->min_latency)->toEqual(0.62);
    expect($actual->max_latency)->toEqual(0.93);
    expect($actual->avg_latency)->toEqual(0.71);
    expect($actual->duplicates)->toEqual(0);
    expect($actual->exit_code)->toEqual(0);
});

test('partial down ping', function () {
    $output = "192.168.1.7 : xmt/rcv/%loss = 5/3/40%, min/avg/max = 0.13/0.23/0.32\n";
    mockFpingProcess($output, 0);

    $actual = app()->make(Fping::class)->ping('192.168.1.7');

    expect($actual->success())->toBeTrue();
    expect($actual->host)->toEqual('192.168.1.7');
    expect($actual->transmitted)->toEqual(5);
    expect($actual->received)->toEqual(3);
    expect($actual->loss)->toEqual(40);
    expect($actual->min_latency)->toEqual(0.13);
    expect($actual->max_latency)->toEqual(0.32);
    expect($actual->avg_latency)->toEqual(0.23);
    expect($actual->duplicates)->toEqual(0);
    expect($actual->exit_code)->toEqual(0);
});

test('down ping', function () {
    $output = "192.168.53.1 : xmt/rcv/%loss = 3/0/100%\n";
    mockFpingProcess($output, 1);

    $actual = app()->make(Fping::class)->ping('192.168.53.1');

    expect($actual->success())->toBeFalse();
    expect($actual->host)->toEqual('192.168.53.1');
    expect($actual->transmitted)->toEqual(3);
    expect($actual->received)->toEqual(0);
    expect($actual->loss)->toEqual(100);
    expect($actual->min_latency)->toEqual(0.0);
    expect($actual->max_latency)->toEqual(0.0);
    expect($actual->avg_latency)->toEqual(0.0);
    expect($actual->duplicates)->toEqual(0);
    expect($actual->exit_code)->toEqual(1);
});

test('duplicate ping', function () {
    $output = <<<'OUT'
192.168.1.2 : duplicate for [0], 84 bytes, 0.91 ms
192.168.1.2 : duplicate for [0], 84 bytes, 0.95 ms
192.168.1.2 : xmt/rcv/%loss = 3/3/0%, min/avg/max = 0.68/0.79/0.91
OUT;

    mockFpingProcess($output, 1);

    $actual = app()->make(Fping::class)->ping('192.168.1.2');

    expect($actual->success())->toBeFalse();
    expect($actual->host)->toEqual('192.168.1.2');
    expect($actual->transmitted)->toEqual(3);
    expect($actual->received)->toEqual(3);
    expect($actual->loss)->toEqual(0);
    expect($actual->min_latency)->toEqual(0.68);
    expect($actual->max_latency)->toEqual(0.91);
    expect($actual->avg_latency)->toEqual(0.79);
    expect($actual->duplicates)->toEqual(2);
    expect($actual->exit_code)->toEqual(1);
});

function mockFpingProcess($output, $exitCode)
{
    $process = \Mockery::mock(Process::class);
    $process->shouldReceive('getCommandLine', 'run');
    $process->shouldReceive('getErrorOutput')->andReturn($output);
    $process->shouldReceive('getExitCode')->andReturn($exitCode);

    app()->bind(Process::class, fn ($app, $params) => $process);

    return $process;
}

test('bulk ping', function () {
    $expected = [
        '192.168.1.4' => [3, 3, 0, 0.62, 0.93, 0.71, 0, 0],
        'hostname' => [3, 0, 100, 0.0, 0.0, 0.0, 0, 1],
        'invalid:characters!' => [0, 0, 0, 0.0, 0.0, 0.0, 0, 2],
        '1.1.1.1' => [3, 2, 33, 0.024, 0.054, 0.037, 0, 0],
    ];
    $hosts = array_keys($expected);

    $process = \Mockery::mock(Process::class);
    $process->shouldReceive('setTimeout')->with(LibrenmsConfig::get('rrd.step', 300) * 2);
    $process->shouldReceive('setInput')->with(implode("\n", $hosts) . "\n");
    $process->shouldReceive('getCommandLine');
    $process->shouldReceive('run')->withArgs(function ($callback) {
        // simulate incremental output (not always one full line per callback)
        call_user_func($callback, Process::ERR, "ICMP unreachable\n"); // this line should be ignored
        call_user_func($callback, Process::ERR, "192.168.1.4 : xmt/rcv/%loss = 3/3/0%, min/avg/max = 0.62/0.71/0.93\nhostname    : xmt/rcv/%loss = 3/0/100%");
        call_user_func($callback, Process::ERR, "invalid:characters!: Name or service not known\n\n1.1.1.1 : xmt/rcv/%loss = 3/2/33%");
        call_user_func($callback, Process::ERR, ", min/avg/max = 0.024/0.037/0.054\n");

        return true;
    });

    $this->app->bind(Process::class, fn ($app, $params) => $process);

    // make call
    $calls = 0;
    app()->make(Fping::class)->bulkPing($hosts, function (FpingResponse $response) use ($expected, &$calls): void {
        $calls++;

        expect($expected)->toHaveKey($response->host);
        $current = $expected[$response->host];

        expect($response->transmitted)->toBe($current[0]);
        expect($response->received)->toBe($current[1]);
        expect($response->loss)->toBe($current[2]);
        expect($response->min_latency)->toBe($current[3]);
        expect($response->max_latency)->toBe($current[4]);
        expect($response->avg_latency)->toBe($current[5]);
        expect($response->duplicates)->toBe($current[6]);
        expect($response->exit_code)->toBe($current[7]);
        expect($response->wasSkipped())->toBeFalse();
    });

    expect($calls)->toEqual(count($expected));
});
