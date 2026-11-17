<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Actions\Device\CheckDeviceAvailability;
use App\Models\Device;
use App\Models\DevicePollingMethod;
use LibreNMS\Data\Source\Snmp\RawSnmpResponse;
use LibreNMS\Data\Source\Snmp\SnmpBackendInterface;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Polling\Method\Config\IcmpConfig;
use LibreNMS\Polling\Method\Config\SnmpConfig;
use LibreNMS\Polling\Method\Methods\IcmpPollingMethod;
use LibreNMS\Polling\Method\Methods\SnmpPollingMethod;
use LibreNMS\Polling\Method\PollingMethodRegistry;
use LibreNMS\Polling\Method\ProbeResult;
use LibreNMS\Polling\PerDeviceMethodResults;
use LibreNMS\Polling\Secrets\Data\SnmpCommunitySecretData;

test('device status', function () {
    $icmpResults = [
        ProbeResult::success(['duplicates' => false]),
        ProbeResult::failure(['duplicates' => false]),
        ProbeResult::success(['duplicates' => false]),
        ProbeResult::failure(['duplicates' => false]),
        ProbeResult::success(['duplicates' => false]),
        ProbeResult::failure(['duplicates' => false]),
        ProbeResult::success(['duplicates' => false]),
        ProbeResult::failure(['duplicates' => false]),
    ];

    $snmpResults = [
        ProbeResult::success(),
        ProbeResult::success(),
        ProbeResult::failure(),
        ProbeResult::failure(),
        ProbeResult::success(),
        ProbeResult::success(),
        ProbeResult::failure(),
        ProbeResult::failure(),
    ];

    $icmpMethodMock = Mockery::mock(\LibreNMS\Polling\Method\Methods\PollingMethod::class);
    $icmpMethodMock->shouldReceive('config')->andReturn(new IcmpConfig(ipVersion: 'default'));
    $icmpMethodMock->shouldReceive('probe')->andReturn(...$icmpResults);

    $snmpMethodMock = Mockery::mock(\LibreNMS\Polling\Method\Methods\PollingMethod::class);
    $snmpMethodMock->shouldReceive('config')->andReturn(new IcmpConfig(ipVersion: 'default'));
    $snmpMethodMock->shouldReceive('probe')->andReturn(...$snmpResults);

    $device = new Device();
    $icmpMethod = new DevicePollingMethod([
        'method_type' => PollingMethodType::Icmp,
        'enabled' => true,
        'affects_availability' => true,
    ]);
    $icmpMethod->setRelation('device', $device);

    $snmpMethod = new DevicePollingMethod([
        'method_type' => PollingMethodType::Snmp,
        'enabled' => true,
        'affects_availability' => true,
    ]);
    $snmpMethod->setRelation('device', $device);

    $device->setRelation('pollingMethods', collect([$icmpMethod, $snmpMethod]));

    $this->app->instance(IcmpPollingMethod::class, $icmpMethodMock);
    $this->app->instance(SnmpPollingMethod::class, $snmpMethodMock);

    /** ping and snmp enabled */
    $icmpMethod->enabled = true;
    $snmpMethod->enabled = true;

    // ping up, snmp up
    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeTrue();
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toEqual('');
    expect($device->polling()->failedAvailabilityChecks()->isEmpty())->toBeTrue();

    // ping down, snmp up
    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeFalse();
    expect($device->status)->toBeFalse();
    expect($device->status_reason)->toEqual('icmp');
    expect($device->polling()->failedAvailabilityChecks()->isEmpty())->toBeFalse();

    // ping up, snmp down
    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeFalse();
    expect($device->status)->toBeFalse();
    expect($device->status_reason)->toEqual('snmp');
    expect($device->polling()->failedAvailabilityChecks()->isEmpty())->toBeFalse();

    // ping down, snmp down
    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeFalse();
    expect($device->status)->toBeFalse();
    expect($device->status_reason)->toEqual('icmp,snmp');
    expect($device->polling()->failedAvailabilityChecks()->isEmpty())->toBeFalse();

    /** ping disabled and snmp enabled */
    $device->status = true;
    $device->status_reason = '';
    $icmpMethod->enabled = false;
    $snmpMethod->enabled = true;

    // ping up, snmp up
    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeTrue();
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toEqual('');
    expect($device->polling()->failedAvailabilityChecks()->isEmpty())->toBeTrue();

    // ping down, snmp up
    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeTrue();
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toEqual('');
    expect($device->polling()->failedAvailabilityChecks()->isEmpty())->toBeTrue();

    // ping up, snmp down
    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeFalse();
    expect($device->status)->toBeFalse();
    expect($device->status_reason)->toEqual('snmp');
    expect($device->polling()->failedAvailabilityChecks()->isEmpty())->toBeFalse();

    // ping down, snmp down
    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeFalse();
    expect($device->status)->toBeFalse();
    expect($device->status_reason)->toEqual('snmp');
    expect($device->polling()->failedAvailabilityChecks()->isEmpty())->toBeFalse();

    /** ping enabled and snmp disabled */
    $device->status = true;
    $device->status_reason = '';
    $icmpMethod->enabled = true;
    $snmpMethod->enabled = false;

    // ping up, snmp up
    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeTrue();
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toEqual('');
    expect($device->polling()->failedAvailabilityChecks()->isEmpty())->toBeTrue();

    // ping down, snmp up
    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeFalse();
    expect($device->status)->toBeFalse();
    expect($device->status_reason)->toEqual('icmp');
    expect($device->polling()->failedAvailabilityChecks()->isEmpty())->toBeFalse();

    // ping up, snmp down
    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeTrue();
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toEqual('');
    expect($device->polling()->failedAvailabilityChecks()->isEmpty())->toBeTrue();

    // ping down, snmp down
    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeFalse();
    expect($device->status)->toBeFalse();
    expect($device->status_reason)->toEqual('icmp');
    expect($device->polling()->failedAvailabilityChecks()->isEmpty())->toBeFalse();

    /** ping and snmp disabled */
    $device->status = true;
    $device->status_reason = '';
    $icmpMethod->enabled = false;
    $snmpMethod->enabled = false;

    // ping up, snmp up
    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeTrue();
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toEqual('');
    expect($device->polling()->failedAvailabilityChecks()->isEmpty())->toBeTrue();

    // ping down, snmp up
    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeTrue();
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toEqual('');
    expect($device->polling()->failedAvailabilityChecks()->isEmpty())->toBeTrue();

    // ping up, snmp down
    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeTrue();
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toEqual('');
    expect($device->polling()->failedAvailabilityChecks()->isEmpty())->toBeTrue();

    // ping down, snmp down
    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeTrue();
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toEqual('');
    expect($device->polling()->failedAvailabilityChecks()->isEmpty())->toBeTrue();
});

test('is snmpable', function () {
    $mockBackend = Mockery::mock(SnmpBackendInterface::class);
    $mockBackend->shouldReceive('get')
        ->times(4)
        ->andReturn(
            new RawSnmpResponse('SNMPv2-MIB::sysObjectID.0 = OID: .1', '', 0),
            new RawSnmpResponse('SNMPv2-MIB::sysObjectID.0 = OID: .1', '', 2),
            new RawSnmpResponse('SNMPv2-MIB::sysObjectID.0 = OID: .1', '', 1),
            new RawSnmpResponse('', 'Timeout', 1)
        );
    $this->app->instance(SnmpBackendInterface::class, $mockBackend);

    $device = new Device;
    $snmpConfig = SnmpConfig::make(
        [
            'transport' => 'udp',
            'port' => 161,
            'context' => null,
            'timeout' => 3,
            'retries' => 1,
            'max_repeaters' => 0,
            'max_oid' => 10,
        ] + (new SnmpPollingMethod)->defaults(),
        new SnmpCommunitySecretData(
            community: 'public',
        ),
    );

    $snmpMethod = app(PollingMethodRegistry::class)->get(PollingMethodType::Snmp);

    expect($snmpMethod->probe($device, $snmpConfig)->isSuccess())->toBeTrue();
    expect($snmpMethod->probe($device, $snmpConfig)->isSuccess())->toBeTrue();
    expect($snmpMethod->probe($device, $snmpConfig)->isSuccess())->toBeTrue();
    expect($snmpMethod->probe($device, $snmpConfig)->isSuccess())->toBeFalse();
});
