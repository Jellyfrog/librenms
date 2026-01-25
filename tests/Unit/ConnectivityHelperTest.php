<?php

use App\Actions\Device\CheckDeviceAvailability;
use App\Actions\Device\DeviceIsSnmpable;
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use LibreNMS\Data\Source\Fping;
use LibreNMS\Data\Source\FpingResponse;
use LibreNMS\Data\Source\SnmpResponse;
use SnmpQuery;

test('device status', function () {
    // not called when ping is disabled
    $this->app->singleton(Fping::class, function () {
        $mock = Mockery::mock(Fping::class);
        $up = FpingResponse::artificialUp();
        $down = FpingResponse::artificialDown();
        $mock->shouldReceive('ping')
            ->times(8)
            ->andReturn(
                $up,
                $down,
                $up,
                $down,
                $up,
                $down,
                $up,
                $down
            );

        return $mock;
    });

    // not called when snmp is disabled or ping up
    $up = new SnmpResponse('SNMPv2-MIB::sysObjectID.0 = .1');
    $down = new SnmpResponse('', '', 1);
    SnmpQuery::partialMock()->shouldReceive('get')
        ->times(6)
        ->andReturn(
            $up,
            $down,
            $up,
            $up,
            $down,
            $down
        );

    $device = new Device();

    /** ping and snmp enabled */
    LibrenmsConfig::set('icmp_check', true);
    $device->snmp_disable = false;

    // ping up, snmp up
    expect(app(CheckDeviceAvailability::class)->execute($device))->toBeTrue();
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toBe('');

    // ping down, snmp up
    expect(app(CheckDeviceAvailability::class)->execute($device))->toBeFalse();
    expect($device->status)->toBeFalse();
    expect($device->status_reason)->toBe('icmp');

    // ping up, snmp down
    expect(app(CheckDeviceAvailability::class)->execute($device))->toBeFalse();
    expect($device->status)->toBeFalse();
    expect($device->status_reason)->toBe('snmp');

    // ping down, snmp down
    expect(app(CheckDeviceAvailability::class)->execute($device))->toBeFalse();
    expect($device->status)->toBeFalse();
    expect($device->status_reason)->toBe('icmp');

    /** ping disabled and snmp enabled */
    LibrenmsConfig::set('icmp_check', false);
    $device->snmp_disable = false;

    // ping up, snmp up
    expect(app(CheckDeviceAvailability::class)->execute($device))->toBeTrue();
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toBe('');

    // ping down, snmp up
    expect(app(CheckDeviceAvailability::class)->execute($device))->toBeTrue();
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toBe('');

    // ping up, snmp down
    expect(app(CheckDeviceAvailability::class)->execute($device))->toBeFalse();
    expect($device->status)->toBeFalse();
    expect($device->status_reason)->toBe('snmp');

    // ping down, snmp down
    expect(app(CheckDeviceAvailability::class)->execute($device))->toBeFalse();
    expect($device->status)->toBeFalse();
    expect($device->status_reason)->toBe('snmp');

    /** ping enabled and snmp disabled */
    LibrenmsConfig::set('icmp_check', true);
    $device->snmp_disable = true;

    // ping up, snmp up
    expect(app(CheckDeviceAvailability::class)->execute($device))->toBeTrue();
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toBe('');

    // ping down, snmp up
    expect(app(CheckDeviceAvailability::class)->execute($device))->toBeFalse();
    expect($device->status)->toBeFalse();
    expect($device->status_reason)->toBe('icmp');

    // ping up, snmp down
    expect(app(CheckDeviceAvailability::class)->execute($device))->toBeTrue();
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toBe('');

    // ping down, snmp down
    expect(app(CheckDeviceAvailability::class)->execute($device))->toBeFalse();
    expect($device->status)->toBeFalse();
    expect($device->status_reason)->toBe('icmp');

    /** ping and snmp disabled */
    LibrenmsConfig::set('icmp_check', false);
    $device->snmp_disable = true;

    // ping up, snmp up
    expect(app(CheckDeviceAvailability::class)->execute($device))->toBeTrue();
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toBe('');

    // ping down, snmp up
    expect(app(CheckDeviceAvailability::class)->execute($device))->toBeTrue();
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toBe('');

    // ping up, snmp down
    expect(app(CheckDeviceAvailability::class)->execute($device))->toBeTrue();
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toBe('');

    // ping down, snmp down
    expect(app(CheckDeviceAvailability::class)->execute($device))->toBeTrue();
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toBe('');
});

test('is snmpable', function () {
    SnmpQuery::partialMock()->shouldReceive('get')
        ->times(4)
        ->andReturn(
            new SnmpResponse('SNMPv2-MIB::sysObjectID.0 = .1', '', 0),
            new SnmpResponse('SNMPv2-MIB::sysObjectID.0 = .1', '', 1),
            new SnmpResponse('', '', 0),
            new SnmpResponse('', '', 1)
        );

    $device = new Device;

    expect((new DeviceIsSnmpable)->execute($device))->toBeTrue();
    expect((new DeviceIsSnmpable)->execute($device))->toBeTrue();
    expect((new DeviceIsSnmpable)->execute($device))->toBeTrue();
    expect((new DeviceIsSnmpable)->execute($device))->toBeFalse();
});
