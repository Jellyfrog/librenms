<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\Qos;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function pollQos(int $packetsOut, int $packetsDropOut): Qos
{
    $device = Device::factory()->create();
    $qos = Qos::create([
        'device_id' => $device->device_id,
        'type' => 'cisco_cbqos_classmap',
        'title' => 'class-default',
        'rrd_id' => 'port-1-cbqos-1-2',
        'snmp_idx' => '1.2',
        'ingress' => 0,
        'egress' => 1,
    ]);
    $qos->forceFill(['last_polled' => 1000, 'last_packets_out' => 0, 'last_packets_drop_out' => 0])->save();

    $qos->forceFill(['last_polled' => 1300, 'last_packets_out' => $packetsOut, 'last_packets_drop_out' => $packetsDropOut])->save();

    return $qos->fresh();
}

test('drop percentage calculated', function (): void {
    $qos = pollQos(3000, 300);

    expect($qos->packets_out_rate)->toEqual(10);
    expect($qos->packets_drop_out_rate)->toEqual(1);
    expect($qos->packets_drop_out_pct)->toEqual(0.1);
});

test('drops exceeding pre policy counters do not overflow', function (): void {
    // Catalyst 3850 reports drops while pre-policy counters stay near zero
    $qos = pollQos(300, 300000000);

    expect($qos->packets_out_rate)->toEqual(1);
    expect($qos->packets_drop_out_rate)->toEqual(1000000);
    expect($qos->packets_drop_out_pct)->toBeNull();
});

test('zero pre policy counters', function (): void {
    $qos = pollQos(0, 300000000);

    expect($qos->packets_drop_out_rate)->toEqual(1000000);
    expect($qos->packets_drop_out_pct)->toBeNull();
});