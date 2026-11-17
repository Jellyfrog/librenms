<?php

use LibreNMS\Exceptions\RrdCachedConnectionException;
use LibreNMS\Exceptions\RrdCorruptionException;
use LibreNMS\Exceptions\RrdDsMismatchException;
use LibreNMS\Exceptions\RrdException;
use LibreNMS\Exceptions\RrdExecutableNotFoundException;
use LibreNMS\Exceptions\RrdNotFoundException;
use LibreNMS\Exceptions\RrdPermissionException;
use LibreNMS\Exceptions\RrdUnknownException;
use LibreNMS\Exceptions\RrdUpdateTooFrequentException;
use LibreNMS\RRD\RrdProcess;
use LibreNMS\Util\Binaries;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

uses(LibreNMS\Tests\TestCase::class);

beforeEach(function (): void {
    $this->logger = Mockery::mock(LoggerInterface::class);
    $this->logger->shouldReceive('debug')->byDefault();

    $this->process = Mockery::mock(Process::class);
    $this->process->shouldReceive('setInput')->byDefault();
    $this->process->shouldReceive('setTimeout')->byDefault();
    $this->process->shouldReceive('setIdleTimeout')->byDefault();
    $this->process->shouldReceive('start')->byDefault();
    $this->process->shouldReceive('isRunning')->andReturn(false, true)->byDefault();
    $this->process->shouldReceive('clearOutput')->byDefault();
    $this->process->shouldReceive('stop')->byDefault();
});

afterEach(function (): void {
    Mockery::close();
});

test('run command successfully', function (): void {
    $this->process->shouldReceive('getOutput')->andReturn("some output\nOK u:0.01 s:0.02 r:0.03\n");
    $this->process->shouldReceive('waitUntil')->andReturnUsing(fn ($callback) => $callback(Process::OUT, "OK u:0.01 s:0.02 r:0.03\n"));

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);
    $output = $rrdProcess->run('info test.rrd');

    expect($output)->toEqual('some output');
});

test('restarts process that sat idle', function (): void {
    $this->process->shouldReceive('getOutput')->andReturn("OK u:0.01 s:0.02 r:0.03\n");
    $this->process->shouldReceive('waitUntil')->andReturnUsing(fn ($callback) => $callback(Process::OUT, "OK u:0.01 s:0.02 r:0.03\n"));
    $this->process->shouldReceive('isRunning')->andReturn(true);

    $started = 0;
    $rrdProcess = new RrdProcess($this->logger, new Binaries, 0, function () use (&$started) {
        $started++;

        return $this->process;
    });
    $rrdProcess->run('info test.rrd');
    $rrdProcess->run('info test.rrd');

    expect($started)->toBe(2);
});

test('throws not found exception when file is missing', function (): void {
    $this->process->shouldReceive('waitUntil')->andReturnUsing(fn ($callback) => $callback(Process::OUT, "ERROR: No such file test.rrd\n"));

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);

    $this->expectException(RrdNotFoundException::class);
    $this->expectExceptionMessage('No such file test.rrd');

    $rrdProcess->run('info test.rrd');
});

test('throws update too frequent exception', function (): void {
    $this->process->shouldReceive('waitUntil')->andReturnUsing(fn ($callback) => $callback(Process::OUT, "ERROR: illegal attempt to update using time 123456789\n"));

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);

    $this->expectException(RrdUpdateTooFrequentException::class);
    $this->expectExceptionMessage('illegal attempt to update using time 123456789');

    $rrdProcess->run('update test.rrd N:1');
});

test('throws cached connection exception', function (): void {
    $this->process->shouldReceive('waitUntil')->andReturnUsing(fn ($callback) => $callback(Process::OUT, "ERROR: Unable to connect to rrdcached\n"));

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);

    $this->expectException(RrdCachedConnectionException::class);
    $this->expectExceptionMessage('Unable to connect to rrdcached');

    $rrdProcess->run('update test.rrd N:1');
});

test('throws cached connection exception when socket missing', function (): void {
    $this->process->shouldReceive('waitUntil')->andReturnUsing(fn ($callback) => $callback(Process::OUT, "ERROR: Unable to connect to rrdcached: No such file or directory.\n"));

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);

    $this->expectException(RrdCachedConnectionException::class);
    $this->expectExceptionMessage('Unable to connect to rrdcached: No such file or directory.');

    $rrdProcess->run('update test.rrd N:1');
});

test('throws generic rrd exception', function (): void {
    $this->process->shouldReceive('waitUntil')->andReturnUsing(fn ($callback) => $callback(Process::OUT, "ERROR: something went wrong\n"));

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);

    $this->expectException(RrdException::class);
    $this->expectExceptionMessage('something went wrong');

    $rrdProcess->run('update test.rrd N:1');
});

test('throws exception on datasource count mismatch', function (): void {
    $this->process->shouldReceive('waitUntil')->andReturnUsing(fn ($callback) => $callback(Process::OUT, "ERROR: test.rrd: expected 2 data source readings (got 1) from N\n"));

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);

    $this->expectException(RrdDsMismatchException::class);
    $this->expectExceptionMessage('expected 2 data source readings (got 1) from N');

    $rrdProcess->run('update test.rrd N:1');
});

test('throws exception on unknown datasource name', function (): void {
    $this->process->shouldReceive('waitUntil')->andReturnUsing(fn ($callback) => $callback(Process::OUT, "ERROR: unknown DS name 'nonexistent'\n"));

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);

    $this->expectException(RrdDsMismatchException::class);
    $this->expectExceptionMessage("unknown DS name 'nonexistent'");

    $rrdProcess->run('update test.rrd -t nonexistent N:1');
});

test('throws exception on extra data in update argument', function (): void {
    $this->process->shouldReceive('waitUntil')->andReturnUsing(fn ($callback) => $callback(Process::OUT, "ERROR: test.rrd: found extra data on update argument: 2:3\n"));

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);

    $this->expectException(RrdDsMismatchException::class);
    $this->expectExceptionMessage('found extra data on update argument: 2:3');

    $rrdProcess->run('update test.rrd N:1:2:3');
});

test('throws exception on expected timestamp not found in data source', function (): void {
    $this->process->shouldReceive('waitUntil')->andReturnUsing(fn ($callback) => $callback(
        Process::OUT,
        "ERROR: test.rrd: expected timestamp not found in data source from foo=2\n"
    ));

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);

    $this->expectException(RrdDsMismatchException::class);
    $this->expectExceptionMessage(
        'expected timestamp not found in data source from foo=2'
    );

    $rrdProcess->run('update test.rrd foo=2');
});

test('throws exception on permission denied', function (): void {
    $this->process->shouldReceive('waitUntil')->andReturnUsing(fn ($callback) => $callback(Process::OUT, "ERROR: opening 'test.rrd': Permission denied\n"));

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);

    $this->expectException(RrdPermissionException::class);
    $this->expectExceptionMessage("opening 'test.rrd': Permission denied");

    $rrdProcess->run('update test.rrd N:1');
});

test('throws exception on cannot create temporary file', function (): void {
    $this->process->shouldReceive('waitUntil')->andReturnUsing(fn ($callback) => $callback(Process::OUT, "ERROR: Cannot create temporary file\n"));

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);

    $this->expectException(RrdPermissionException::class);
    $this->expectExceptionMessage('Cannot create temporary file');

    $rrdProcess->run('update test.rrd N:1');
});

test('throws exception on corrupt rrd file', function (): void {
    $this->process->shouldReceive('waitUntil')->andReturnUsing(fn ($callback) => $callback(Process::OUT, "ERROR: reached EOF while loading header rrd->stat_head\n"));

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);

    $this->expectException(RrdCorruptionException::class);
    $this->expectExceptionMessage('reached EOF while loading header rrd->stat_head');

    $rrdProcess->run('info test.rrd');
});

test('throws exception on error output on stderr', function (): void {
    $this->process->shouldReceive('waitUntil')->andReturnUsing(fn ($callback) => $callback(Process::ERR, "ERROR: some error occurred\n"));

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);

    $this->expectException(RrdException::class);
    $this->expectExceptionMessage('some error occurred');

    $rrdProcess->run('update test.rrd N:1');
});

test('logs warning on generic stderr output', function (): void {
    $this->logger->shouldReceive('warning')->with('RRDtool stderr: Fontconfig warning: something')->once();

    $this->process->shouldReceive('waitUntil')->andReturnUsing(function ($callback) {
        expect($callback(Process::ERR, "Fontconfig warning: something\n"))->toBeFalse();

        return $callback(Process::OUT, "OK u:0.01 s:0.02 r:0.03\n");
    });
    $this->process->shouldReceive('getOutput')->andReturn("OK u:0.01 s:0.02 r:0.03\n");

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);
    $output = $rrdProcess->run('update test.rrd N:1');

    expect($output)->toEqual('');
});

test('ignores empty stderr output', function (): void {
    $this->logger->shouldNotReceive('warning');

    $this->process->shouldReceive('waitUntil')->andReturnUsing(function ($callback) {
        expect($callback(Process::ERR, "  \n  "))->toBeFalse();

        return $callback(Process::OUT, "OK u:0.01 s:0.02 r:0.03\n");
    });
    $this->process->shouldReceive('getOutput')->andReturn("OK u:0.01 s:0.02 r:0.03\n");

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);
    $output = $rrdProcess->run('update test.rrd N:1');

    expect($output)->toEqual('');
});

test('throws exception when rrdtool not found', function (): void {
    $this->process->shouldReceive('waitUntil')->andReturnUsing(fn ($callback) => $callback(Process::ERR, "sh: line 1: exec: rrdtool: not found\n"));

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);

    $this->expectException(RrdExecutableNotFoundException::class);
    $this->expectExceptionMessage('sh: line 1: exec: rrdtool: not found');

    $rrdProcess->run('info test.rrd');
});

test('throws unknown exception for unclassified errors', function (): void {
    $this->process->shouldReceive('waitUntil')->andReturnUsing(fn ($callback) => $callback(Process::OUT, "ERROR: something completely unexpected\n"));

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);

    $this->expectException(RrdUnknownException::class);
    $this->expectExceptionMessage('something completely unexpected');

    $rrdProcess->run('update test.rrd N:1');
});

test('can wait for custom string', function (): void {
    $this->process->shouldReceive('getOutput')->andReturn("some output\nDONE\n");
    $this->process->shouldReceive('waitUntil')->andReturnUsing(fn ($callback) => $callback(Process::OUT, "DONE\n"));

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);
    $output = $rrdProcess->run('info test.rrd', 'DONE');

    expect($output)->toEqual("some output\nDONE");
});

test('start process only if not running', function (): void {
    $process = Mockery::mock(Process::class);
    $process->shouldReceive('setInput');
    $process->shouldReceive('setTimeout');
    $process->shouldReceive('setIdleTimeout');
    $process->shouldReceive('start')->once();
    $process->shouldReceive('isRunning')->andReturn(true);
    $process->shouldReceive('stop');

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $process);
    $rrdProcess->start();
    // null -> start
    $rrdProcess->start();

    // isRunning(true) -> don't start
    $this->expectNotToPerformAssertions();
});

test('stop process', function (): void {
    $this->process->shouldReceive('stop')->once();

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);
    $rrdProcess->start();
    $rrdProcess->stop();

    $this->expectNotToPerformAssertions();
});

test('destructor stops process', function (): void {
    $this->process->shouldReceive('stop')->once();

    $rrdProcess = new RrdProcess($this->logger, new Binaries, 300, fn () => $this->process);
    $rrdProcess->start();

    unset($rrdProcess);
    $this->expectNotToPerformAssertions();
});