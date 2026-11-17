<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Events\SnmpQueryExecuted;
use App\Listeners\SnmpMetricListener;
use App\Polling\Measure\MeasurementManager;
use LibreNMS\Data\Source\Snmp\SnmpQueryOptions;
use LibreNMS\Data\Source\Snmp\SnmpResponse;

test('records snmp measurement', function () {
    $manager = app(MeasurementManager::class);
    $initialCount = $manager->getCategory('snmp')->getTotalCount();
    $initialDuration = $manager->getCategory('snmp')->getTotalDuration();

    $event = new SnmpQueryExecuted(
        target: '127.0.0.1',
        method: 'snmpget',
        oids: ['sysDescr.0'],
        duration: 0.123,
        response: new SnmpResponse(['sysDescr.0' => 'Linux']),
        options: new SnmpQueryOptions,
        config: \LibreNMS\Polling\Method\Config\SnmpConfig::make((new \LibreNMS\Polling\Method\Methods\SnmpPollingMethod)->defaults(), new \LibreNMS\Polling\Secrets\Data\SnmpCommunitySecretData),
        backend: 'NetSnmp',
    );

    new SnmpMetricListener()->handle($event);

    expect($manager->getCategory('snmp')->getTotalCount())->toBe($initialCount + 1);
    expect($manager->getCategory('snmp')->getTotalDuration())->toEqualWithDelta($initialDuration + 0.123, 0.0001);
});

test('records snmp backend measurement', function () {
    $manager = app(MeasurementManager::class);
    $initialCount = $manager->getCategory('snmp_backend')->getTotalCount();
    $initialDuration = $manager->getCategory('snmp_backend')->getTotalDuration();

    $event = new SnmpQueryExecuted(
        target: '127.0.0.1',
        method: 'snmpget',
        oids: ['sysDescr.0'],
        duration: 0.045,
        response: new SnmpResponse(['sysDescr.0' => 'Linux']),
        options: new SnmpQueryOptions,
        config: \LibreNMS\Polling\Method\Config\SnmpConfig::make((new \LibreNMS\Polling\Method\Methods\SnmpPollingMethod)->defaults(), new \LibreNMS\Polling\Secrets\Data\SnmpCommunitySecretData),
        backend: 'PhpExtension',
    );

    new SnmpMetricListener()->handle($event);

    expect($manager->getCategory('snmp_backend')->getTotalCount())->toBe($initialCount + 1);
    expect($manager->getCategory('snmp_backend')->getTotalDuration())->toEqualWithDelta($initialDuration + 0.045, 0.0001);
});
