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

use App\Facades\LibrenmsConfig;
use LibreNMS\Data\Source\Icmp\Fping;
use LibreNMS\Data\Source\Icmp\FpingOutputBuffer;
use LibreNMS\Data\Source\Icmp\FpingResponse;
use Symfony\Component\Process\Process;

uses(LibreNMS\Tests\TestCase::class);

test('up ping', function (): void {
    $output = "192.168.1.3 : xmt/rcv/%loss = 3/3/0%, min/avg/max = 0.62/0.71/0.93\n";
    mockFpingProcess($output, 0);

    $actual = app()->make(Fping::class)->ping('192.168.1.3');

    expect($actual->isAlive())->toBeTrue();
    expect($actual->host)->toEqual('192.168.1.3');
    expect($actual->transmitted)->toEqual(3);
    expect($actual->received)->toEqual(3);
    expect($actual->loss)->toEqual(0);
    expect($actual->min_latency)->toEqual(0.62);
    expect($actual->max_latency)->toEqual(0.93);
    expect($actual->avg_latency)->toEqual(0.71);
    expect($actual->duplicates)->toEqual(0);
    expect($actual->getExitCode()->value)->toEqual(0);
});

test('partial down ping', function (): void {
    $output = "192.168.1.7 : xmt/rcv/%loss = 5/3/40%, min/avg/max = 0.13/0.23/0.32\n";
    mockFpingProcess($output, 0);

    $actual = app()->make(Fping::class)->ping('192.168.1.7');

    expect($actual->isAlive())->toBeTrue();
    expect($actual->host)->toEqual('192.168.1.7');
    expect($actual->transmitted)->toEqual(5);
    expect($actual->received)->toEqual(3);
    expect($actual->loss)->toEqual(40);
    expect($actual->min_latency)->toEqual(0.13);
    expect($actual->max_latency)->toEqual(0.32);
    expect($actual->avg_latency)->toEqual(0.23);
    expect($actual->duplicates)->toEqual(0);
    expect($actual->getExitCode()->value)->toEqual(0);
});

test('down ping', function (): void {
    $output = "192.168.53.1 : xmt/rcv/%loss = 3/0/100%\n";
    mockFpingProcess($output, 1);

    $actual = app()->make(Fping::class)->ping('192.168.53.1');

    expect($actual->isAlive())->toBeFalse();
    expect($actual->host)->toEqual('192.168.53.1');
    expect($actual->transmitted)->toEqual(3);
    expect($actual->received)->toEqual(0);
    expect($actual->loss)->toEqual(100);
    expect($actual->min_latency)->toEqual(0.0);
    expect($actual->max_latency)->toEqual(0.0);
    expect($actual->avg_latency)->toEqual(0.0);
    expect($actual->duplicates)->toEqual(0);
    expect($actual->getExitCode()->value)->toEqual(1);
});

test('duplicate ping', function (): void {
    $output = <<<'OUT'
192.168.1.2 : duplicate for [0], 84 bytes, 0.91 ms
192.168.1.2 : duplicate for [0], 84 bytes, 0.95 ms
192.168.1.2 : xmt/rcv/%loss = 3/3/0%, min/avg/max = 0.68/0.79/0.91
OUT;

    mockFpingProcess($output, 1);

    $actual = app()->make(Fping::class)->ping('192.168.1.2');

    expect($actual->isAlive())->toBeFalse();
    expect($actual->host)->toEqual('192.168.1.2');
    expect($actual->transmitted)->toEqual(3);
    expect($actual->received)->toEqual(3);
    expect($actual->loss)->toEqual(0);
    expect($actual->min_latency)->toEqual(0.68);
    expect($actual->max_latency)->toEqual(0.91);
    expect($actual->avg_latency)->toEqual(0.79);
    expect($actual->duplicates)->toEqual(2);
    expect($actual->getExitCode()->value)->toEqual(1);
});

function mockFpingProcess($output, $exitCode)
{
    $process = Mockery::mock(Process::class);
    $process->shouldReceive('getCommandLine', 'run');
    $process->shouldReceive('getErrorOutput')->andReturn($output);
    $process->shouldReceive('getExitCode')->andReturn($exitCode);

    app()->bind(Process::class, fn ($app, $params) => $process);

    return $process;
}

test('bulk ping', function (): void {
    $expected = [
        '192.168.1.4' => [0],
        'hostname' => [1],
        'invalid:characters!' => [2],
        '1.1.1.1' => [0],
    ];
    $hosts = array_keys($expected);

    $process = Mockery::mock(Process::class);
    $process->shouldReceive('setTimeout')->with(LibrenmsConfig::get('rrd.step', 300) * 2);
    $process->shouldReceive('setInput')->with(implode("\n", $hosts) . "\n");
    $process->shouldReceive('getCommandLine');
    $process->shouldReceive('run')->withArgs(function ($callback) {
        // simulate incremental output (not always one full line per callback)
        call_user_func($callback, Process::ERR, "ICMP unreachable\n"); // this line should be ignored
        call_user_func($callback, Process::ERR, "192.168.1.4 is alive\nhostname is unreachable\n");
        call_user_func($callback, Process::ERR, "invalid:characters!: Name or service not known\n\n1.1.1.1 is alive\n");

        return true;
    });

    $this->app->bind(Process::class, fn ($app, $params) => $process);

    // make call
    $calls = 0;
    app()->make(Fping::class)->bulkPing($hosts, function ($response) use ($expected, &$calls): void {
        $calls++;

        expect($expected)->toHaveKey($response->host);
        $current = $expected[$response->host];

        expect($response->getExitCode()->value)->toBe($current[0]);
    });

    expect($calls)->toEqual(count($expected));
});

test('mtu success', function (): void {
    $process = mockFpingProcess('', 0);
    $process->shouldReceive('disableOutput')->once();
    $process->shouldReceive('isSuccessful')->once()->andReturn(true);

    $result = app()->make(Fping::class)->testMtu('192.168.1.3', 1500);
    expect($result)->toBeTrue();
});

test('mtu failure', function (): void {
    $process = mockFpingProcess('', 1);
    $process->shouldReceive('disableOutput')->once();
    $process->shouldReceive('isSuccessful')->once()->andReturn(false);

    $result = app()->make(Fping::class)->testMtu('192.168.1.3', 1500);
    expect($result)->toBeFalse();
});

test('ping command with address families', function (): void {
    config(['librenms.binaries.fping6' => '/nonexistent']);

    $capturedCommands = [];
    $this->app->bind(Process::class, function ($app, $params) use (&$capturedCommands) {
        $capturedCommands[] = $params['command'];
        $process = Mockery::mock(Process::class);
        $process->shouldReceive('getCommandLine', 'run');
        $process->shouldReceive('getErrorOutput')->andReturn("192.168.1.3 : xmt/rcv/%loss = 3/3/0%, min/avg/max = 0.62/0.71/0.93\n");
        $process->shouldReceive('getExitCode')->andReturn(0);

        return $process;
    });

    // Default (null address family) -> no -4 or -6
    app()->make(Fping::class)->ping('192.168.1.3');
    expect($capturedCommands[0])->not->toContain('-4');
    expect($capturedCommands[0])->not->toContain('-6');

    // IPv4 -> includes -4
    app()->make(Fping::class)->ping('192.168.1.3', \LibreNMS\Enum\AddressFamily::IPv4);
    expect($capturedCommands[1])->toContain('-4');
    expect($capturedCommands[1])->not->toContain('-6');

    // IPv6 -> includes -6
    app()->make(Fping::class)->ping('2001:db8::1', \LibreNMS\Enum\AddressFamily::IPv6);
    expect($capturedCommands[2])->toContain('-6');
    expect($capturedCommands[2])->not->toContain('-4');
});

test('mtu command with address families', function (): void {
    config(['librenms.binaries.fping6' => '/nonexistent']);

    $capturedCommands = [];
    $this->app->bind(Process::class, function ($app, $params) use (&$capturedCommands) {
        $capturedCommands[] = $params['command'];
        $process = Mockery::mock(Process::class);
        $process->shouldReceive('getCommandLine', 'run', 'disableOutput');
        $process->shouldReceive('isSuccessful')->andReturn(true);

        return $process;
    });

    // Default (null address family) -> no -4 or -6
    app()->make(Fping::class)->testMtu('192.168.1.3', 1500);
    expect($capturedCommands[0])->not->toContain('-4');
    expect($capturedCommands[0])->not->toContain('-6');

    // IPv4 -> includes -4
    app()->make(Fping::class)->testMtu('192.168.1.3', 1500, \LibreNMS\Enum\AddressFamily::IPv4);
    expect($capturedCommands[1])->toContain('-4');

    // IPv6 -> includes -6
    app()->make(Fping::class)->testMtu('2001:db8::1', 1500, \LibreNMS\Enum\AddressFamily::IPv6);
    expect($capturedCommands[2])->toContain('-6');
});

test('output buffer chunks', function (): void {
    $buffer = new FpingOutputBuffer;
    $hosts = fn (array $responses): array => array_map(fn (FpingResponse $response) => [$response->host, $response->isAlive()], $responses);

    $this->assertSame([], $buffer->push(Process::OUT, '192.0.2.1 is al'));
    $this->assertSame([], $buffer->push(Process::ERR, "ICMP Host Unreachable from 192.0.2.254\n"), 'unrelated lines are ignored');
    $this->assertSame([['192.0.2.1', true], ['192.0.2.2', false]], $hosts($buffer->push(Process::OUT, "ive\n192.0.2.2 is unreachable\n192.0.2.3 is alive")));
    $this->assertSame([['192.0.2.3', true]], $hosts($buffer->flush()), 'the last line has no newline');
    $this->assertSame([], $buffer->flush());
});
