<?php

use LibreNMS\Tests\TestCase;

uses(TestCase::class);

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

use App\Facades\LibrenmsConfig;
use LibreNMS\Data\Source\Fping;
use LibreNMS\Data\Source\FpingResponse;
use Symfony\Component\Process\Process;

function mockFpingProcess($output, $exitCode)
{
    $process = \Mockery::mock(Process::class);
    $process->shouldReceive('getCommandLine', 'run');
    $process->shouldReceive('getErrorOutput')->andReturn($output);
    $process->shouldReceive('getExitCode')->andReturn($exitCode);

    app()->bind(Process::class, fn ($app, $params) => $process);

    return $process;
}

test('up ping', function () {
    $output = "192.168.1.3 : xmt/rcv/%loss = 3/3/0%, min/avg/max = 0.62/0.71/0.93\n";
    mockFpingProcess($output, 0);

    $actual = app()->make(Fping::class)->ping('192.168.1.3');

    $this->assertTrue($actual->success());
    $this->assertEquals('192.168.1.3', $actual->host);
    $this->assertEquals(3, $actual->transmitted);
    $this->assertEquals(3, $actual->received);
    $this->assertEquals(0, $actual->loss);
    $this->assertEquals(0.62, $actual->min_latency);
    $this->assertEquals(0.93, $actual->max_latency);
    $this->assertEquals(0.71, $actual->avg_latency);
    $this->assertEquals(0, $actual->duplicates);
    $this->assertEquals(0, $actual->exit_code);
});

test('partial down ping', function () {
    $output = "192.168.1.7 : xmt/rcv/%loss = 5/3/40%, min/avg/max = 0.13/0.23/0.32\n";
    mockFpingProcess($output, 0);

    $actual = app()->make(Fping::class)->ping('192.168.1.7');

    $this->assertTrue($actual->success());
    $this->assertEquals('192.168.1.7', $actual->host);
    $this->assertEquals(5, $actual->transmitted);
    $this->assertEquals(3, $actual->received);
    $this->assertEquals(40, $actual->loss);
    $this->assertEquals(0.13, $actual->min_latency);
    $this->assertEquals(0.32, $actual->max_latency);
    $this->assertEquals(0.23, $actual->avg_latency);
    $this->assertEquals(0, $actual->duplicates);
    $this->assertEquals(0, $actual->exit_code);
});

test('down ping', function () {
    $output = "192.168.53.1 : xmt/rcv/%loss = 3/0/100%\n";
    mockFpingProcess($output, 1);

    $actual = app()->make(Fping::class)->ping('192.168.53.1');

    $this->assertFalse($actual->success());
    $this->assertEquals('192.168.53.1', $actual->host);
    $this->assertEquals(3, $actual->transmitted);
    $this->assertEquals(0, $actual->received);
    $this->assertEquals(100, $actual->loss);
    $this->assertEquals(0.0, $actual->min_latency);
    $this->assertEquals(0.0, $actual->max_latency);
    $this->assertEquals(0.0, $actual->avg_latency);
    $this->assertEquals(0, $actual->duplicates);
    $this->assertEquals(1, $actual->exit_code);
});

test('duplicate ping', function () {
    $output = <<<'OUT'
192.168.1.2 : duplicate for [0], 84 bytes, 0.91 ms
192.168.1.2 : duplicate for [0], 84 bytes, 0.95 ms
192.168.1.2 : xmt/rcv/%loss = 3/3/0%, min/avg/max = 0.68/0.79/0.91
OUT;

    mockFpingProcess($output, 1);

    $actual = app()->make(Fping::class)->ping('192.168.1.2');

    $this->assertFalse($actual->success());
    $this->assertEquals('192.168.1.2', $actual->host);
    $this->assertEquals(3, $actual->transmitted);
    $this->assertEquals(3, $actual->received);
    $this->assertEquals(0, $actual->loss);
    $this->assertEquals(0.68, $actual->min_latency);
    $this->assertEquals(0.91, $actual->max_latency);
    $this->assertEquals(0.79, $actual->avg_latency);
    $this->assertEquals(2, $actual->duplicates);
    $this->assertEquals(1, $actual->exit_code);
});

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

    app()->bind(Process::class, fn ($app, $params) => $process);

    // make call
    $calls = 0;
    app()->make(Fping::class)->bulkPing($hosts, function (FpingResponse $response) use ($expected, &$calls): void {
        $calls++;

        test()->assertArrayHasKey($response->host, $expected);
        $current = $expected[$response->host];

        test()->assertSame($current[0], $response->transmitted);
        test()->assertSame($current[1], $response->received);
        test()->assertSame($current[2], $response->loss);
        test()->assertSame($current[3], $response->min_latency);
        test()->assertSame($current[4], $response->max_latency);
        test()->assertSame($current[5], $response->avg_latency);
        test()->assertSame($current[6], $response->duplicates);
        test()->assertSame($current[7], $response->exit_code);
        test()->assertFalse($response->wasSkipped());
    });

    $this->assertEquals(count($expected), $calls);
});
