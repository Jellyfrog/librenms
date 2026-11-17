<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Models\Device;
use App\Models\Ipv4Address;
use App\Models\Ipv6Address;
use App\Models\Port;
use LibreNMS\Scan\KnownAddresses;
use PHPUnit\Framework\Attributes\TestDox;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('find', function () {
    Device::factory()->create(['hostname' => '192.0.2.1']);
    Device::factory()->create(['hostname' => 'by-ip.example.com', 'ip' => '192.0.2.2']);
    $portDevice = Device::factory()->create(['hostname' => 'by-port.example.com']);
    $port = Port::factory()->create(['device_id' => $portDevice->device_id]);
    Ipv4Address::factory()->create(['ipv4_address' => '192.0.2.3', 'port_id' => $port->port_id]);
    Ipv6Address::factory()->create(['ipv6_address' => '2001:0db8:0000:0000:0000:0000:0000:0003', 'ipv6_compressed' => '2001:db8::3', 'port_id' => $port->port_id]);
    Device::factory()->create(['hostname' => 'by-ipv6.example.com', 'ip' => '2001:db8::2']);

    $known = (new KnownAddresses)->find(['192.0.2.1', '192.0.2.2', '192.0.2.3', '192.0.2.4', '2001:db8::2', '2001:db8::3', '2001:db8::4']);
    ksort($known);

    expect($known)->toBe([
        '192.0.2.1' => '192.0.2.1',
        '192.0.2.2' => 'by-ip.example.com',
        '192.0.2.3' => 'by-port.example.com',
        '2001:db8::2' => 'by-ipv6.example.com',
        '2001:db8::3' => 'by-port.example.com',
    ]);
});