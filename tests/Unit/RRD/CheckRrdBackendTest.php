<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use LibreNMS\RRD\Backend\PhpRrd;
use LibreNMS\RRD\Backend\RrdBackendInterface;
use LibreNMS\RRD\Backend\Rrdtool;
use LibreNMS\ValidationResult;
use LibreNMS\Validations\Rrd\CheckRrdBackend;


test('rrdtool is not checked', function () {
    LibrenmsConfig::set('rrd.backend', 'rrdtool');

    expect((new CheckRrdBackend)->enabled())->toBeFalse();
    expect(app(RrdBackendInterface::class))->toBeInstanceOf(Rrdtool::class);
});

test('php rrd backend', function () {
    LibrenmsConfig::set('rrd.backend', 'php-rrd');
    LibrenmsConfig::set('rrdcached', 'unix:/run/rrdcached.sock');

    if (! extension_loaded('rrd')) {
        expect((new CheckRrdBackend)->validate()->getStatus())->toBe(ValidationResult::FAILURE);

        return;
    }

    expect((new CheckRrdBackend)->validate()->getStatus())->toBe(ValidationResult::SUCCESS);
    expect(app(RrdBackendInterface::class))->toBeInstanceOf(PhpRrd::class);
});
