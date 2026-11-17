<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use LibreNMS\Exceptions\RrdNotFoundException;
use LibreNMS\Util\Smokeping;


beforeEach(function () {
    LibrenmsConfig::set('rrd_dir', '/data/rrd');
    LibrenmsConfig::set('smokeping.dir', '/data/rrd/smokeping');
    LibrenmsConfig::set('smokeping.integration', true);
});

test('rrd file name no cached', function () {
    LibrenmsConfig::set('rrdcached', '');
    $smokeping = new Smokeping(new Device(['type' => 'network']));

    expect($smokeping->rrdFileName('host_example_com.rrd'))->toBe('smokeping/network/host_example_com.rrd');
});

test('rrd file name cached', function () {
    LibrenmsConfig::set('rrdcached', 'rrdcached:42217');
    $smokeping = new Smokeping(new Device(['type' => 'network']));

    expect($smokeping->rrdFileName('host_example_com.rrd'))->toBe('smokeping/network/host_example_com.rrd');
    expect($smokeping->generateFileName('host_example_com.rrd'))->toBe('/data/rrd/smokeping/network/host_example_com.rrd');
});

test('rrd file name cached outside rrd dir', function () {
    LibrenmsConfig::set('rrdcached', 'unix:/run/rrdcached.sock');
    LibrenmsConfig::set('smokeping.dir', '/var/lib/smokeping');
    $smokeping = new Smokeping(new Device(['type' => '']));

    $this->expectException(RrdNotFoundException::class);
    $smokeping->rrdFileName('host.rrd');
});

test('rrd file name external data dir', function () {
    LibrenmsConfig::set('smokeping.dir', '/var/lib/smokeping');
    $smokeping = new Smokeping(new Device(['type' => 'network']));

    expect($smokeping->rrdFileName('host_example_com.rrd'))->toBe('/var/lib/smokeping/network/host_example_com.rrd');
});
