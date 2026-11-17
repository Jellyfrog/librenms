<?php

uses(\LibreNMS\Tests\TestCase::class);
use LibreNMS\Scan\LocalNetworks;

test('finds the networks of physical interfaces', function () {
    $interfaces = [
        'lo' => ['up' => true, 'unicast' => [['family' => 2, 'address' => '127.0.0.1', 'netmask' => '255.0.0.0']]],
        'eth0' => ['up' => true, 'unicast' => [
            ['family' => 17],
            ['family' => 2, 'address' => '192.168.1.20', 'netmask' => '255.255.255.0'],
            ['family' => 10, 'address' => 'fe80::1', 'netmask' => 'ffff:ffff:ffff:ffff::'],
        ]],
        'eth1' => ['up' => true, 'unicast' => [['family' => 2, 'address' => '192.168.1.21', 'netmask' => '255.255.255.0']]],
        'eth2' => ['up' => true, 'unicast' => [['family' => 2, 'address' => '10.20.30.40', 'netmask' => '255.255.0.0']]],
        'eth3' => ['up' => true, 'unicast' => [['family' => 2, 'address' => '172.16.0.5', 'netmask' => '255.255.252.0']]],
        'eth4' => ['up' => false, 'unicast' => [['family' => 2, 'address' => '172.17.5.5', 'netmask' => '255.255.255.0']]],
        'ppp0' => ['up' => true, 'unicast' => [['family' => 2, 'address' => '203.0.113.9', 'netmask' => '255.255.255.255']]],
        'eth5' => ['up' => true, 'unicast' => [['family' => 2, 'address' => '169.254.10.1', 'netmask' => '255.255.0.0']]],
        'docker0' => ['up' => true, 'unicast' => [['family' => 2, 'address' => '172.18.0.1', 'netmask' => '255.255.0.0']]],
        'br-4f2a' => ['up' => true, 'unicast' => [['family' => 2, 'address' => '172.19.0.1', 'netmask' => '255.255.0.0']]],
    ];

    expect(new LocalNetworks(containerMarkers: [])->find($interfaces))->toBe([
        '192.168.1.0/24', // once for both interfaces
        '10.20.30.0/24', // a large network is narrowed to the /24 of the server
        '172.16.0.0/22',
    ]);
});

test('suggests nothing inside acontainer', function () {
    // the interface of a container is a plain eth0 on the container network
    $interfaces = [
        'eth0' => ['up' => true, 'unicast' => [['family' => 2, 'address' => '172.18.0.5', 'netmask' => '255.255.0.0']]],
    ];

    expect(new LocalNetworks(containerMarkers: [__FILE__])->find($interfaces))->toBe([]);
});
