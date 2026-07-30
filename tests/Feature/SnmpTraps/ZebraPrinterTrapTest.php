<?php

use LibreNMS\Enum\Severity;

uses(\LibreNMS\Tests\Feature\SnmpTraps\SnmpTrapTestCase::class);


test('zebra printer head open', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ERROR CONDITION: HEAD OPEN
TRAP,
        'ERROR CONDITION: HEAD OPEN',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 HEAD OPEN',
        [Severity::Warning],
    );
});

test('zebra printer paper out', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: PAPER OUT
TRAP,
        'ALERT: PAPER OUT',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 PAPER OUT',
        [Severity::Error],
    );
});

test('zebra printer ribbon out', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: RIBBON OUT
TRAP,
        'ALERT: RIBBON OUT',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 RIBBON OUT',
        [Severity::Error],
    );
});

test('zebra printer media low', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: MEDIA LOW
TRAP,
        'ALERT: MEDIA LOW',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 MEDIA LOW',
        [Severity::Warning],
    );
});

test('zebra printer job completed', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: PQ JOB COMPLETED
TRAP,
        'ALERT: PQ JOB COMPLETED',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 PQ JOB COMPLETED',
        [Severity::Ok],
    );
});

test('zebra printer cutter jam', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: CUTTER JAM
TRAP,
        'ALERT: CUTTER JAM',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 CUTTER JAM',
        [Severity::Error],
    );
});

test('zebra printer job completed german', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 MELDUNG: Druckauftr Fertg
TRAP,
        'MELDUNG: Druckauftr Fertg',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 PQ JOB COMPLETED (German)',
        [Severity::Ok],
    );
});

test('zebra printer paused', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: PRINTER PAUSED
TRAP,
        'ALERT: PRINTER PAUSED',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 PRINTER PAUSED',
        [Severity::Info],
    );
});

test('zebra printer head element bad', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: HEAD ELEMENT BAD
TRAP,
        'ALERT: HEAD ELEMENT BAD',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 HEAD ELEMENT BAD',
        [Severity::Error],
    );
});

test('zebra printer replace head', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: REPLACE HEAD
TRAP,
        'ALERT: REPLACE HEAD',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 REPLACE HEAD',
        [Severity::Error],
    );
});

test('zebra printer motor overtemp', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: MOTOR OVERTEMP
TRAP,
        'ALERT: MOTOR OVERTEMP',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 MOTOR OVERTEMP',
        [Severity::Error],
    );
});

test('zebra printer printhead shutdown', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: PRINTHEAD SHUTDOWN
TRAP,
        'ALERT: PRINTHEAD SHUTDOWN',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 PRINTHEAD SHUTDOWN',
        [Severity::Error],
    );
});

test('zebra printer thermistor fault', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: THERMISTOR FAULT
TRAP,
        'ALERT: THERMISTOR FAULT',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 THERMISTOR FAULT',
        [Severity::Error],
    );
});

test('zebra printer invalid head', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: INVALID HEAD
TRAP,
        'ALERT: INVALID HEAD',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 INVALID HEAD',
        [Severity::Error],
    );
});

test('zebra printer media cartridge load failure', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: MEDIA CARTRIDGE LOAD FAILURE
TRAP,
        'ALERT: MEDIA CARTRIDGE LOAD FAILURE',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 MEDIA CARTRIDGE LOAD FAILURE',
        [Severity::Error],
    );
});

test('zebra printer paper error', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: PAPER ERROR
TRAP,
        'ALERT: PAPER ERROR',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 PAPER ERROR',
        [Severity::Error],
    );
});

test('zebra printer ribbon auth error', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: RIBBON AUTH ERROR
TRAP,
        'ALERT: RIBBON AUTH ERROR',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 RIBBON AUTH ERROR',
        [Severity::Error],
    );
});

test('zebra printer head too hot', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: HEAD TOO HOT
TRAP,
        'ALERT: HEAD TOO HOT',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 HEAD TOO HOT',
        [Severity::Warning],
    );
});

test('zebra printer head cold', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: HEAD COLD
TRAP,
        'ALERT: HEAD COLD',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 HEAD COLD',
        [Severity::Warning],
    );
});

test('zebra printer supply too hot', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: SUPPLY TOO HOT
TRAP,
        'ALERT: SUPPLY TOO HOT',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 SUPPLY TOO HOT',
        [Severity::Warning],
    );
});

test('zebra printer ribbon low', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: RIBBON LOW
TRAP,
        'ALERT: RIBBON LOW',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 RIBBON LOW',
        [Severity::Warning],
    );
});

test('zebra printer battery low', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: BATTERY LOW
TRAP,
        'ALERT: BATTERY LOW',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 BATTERY LOW',
        [Severity::Warning],
    );
});

test('zebra printer clean printhead', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: CLEAN PRINTHEAD
TRAP,
        'ALERT: CLEAN PRINTHEAD',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 CLEAN PRINTHEAD',
        [Severity::Warning],
    );
});

test('zebra printer rfid error', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: RFID ERROR
TRAP,
        'ALERT: RFID ERROR',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 RFID ERROR',
        [Severity::Warning],
    );
});

test('zebra printer rewind', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: REWIND
TRAP,
        'ALERT: REWIND',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 REWIND',
        [Severity::Warning],
    );
});

test('zebra printer no reader present', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: NO READER PRESENT
TRAP,
        'ALERT: NO READER PRESENT',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 NO READER PRESENT',
        [Severity::Warning],
    );
});

test('zebra printer battery missing', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: BATTERY MISSING
TRAP,
        'ALERT: BATTERY MISSING',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 BATTERY MISSING',
        [Severity::Warning],
    );
});

test('zebra printer media cartridge eject failure', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: MEDIA CARTRIDGE EJECT FAILURE
TRAP,
        'ALERT: MEDIA CARTRIDGE EJECT FAILURE',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 MEDIA CARTRIDGE EJECT FAILURE',
        [Severity::Warning],
    );
});

test('zebra printer media cartridge forced eject', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: MEDIA CARTRIDGE FORCED EJECT
TRAP,
        'ALERT: MEDIA CARTRIDGE FORCED EJECT',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 MEDIA CARTRIDGE FORCED EJECT',
        [Severity::Warning],
    );
});

test('zebra printer ribbon tension', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: RIBBON TENSION
TRAP,
        'ALERT: RIBBON TENSION',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 RIBBON TENSION',
        [Severity::Warning],
    );
});

test('zebra printer cover open', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: COVER OPEN
TRAP,
        'ALERT: COVER OPEN',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 COVER OPEN',
        [Severity::Warning],
    );
});

test('zebra printer clean cutter', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: CLEAN CUTTER
TRAP,
        'ALERT: CLEAN CUTTER',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 CLEAN CUTTER',
        [Severity::Warning],
    );
});

test('zebra printer duplicate ip', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: DUPLICATE IP
TRAP,
        'ALERT: DUPLICATE IP',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 DUPLICATE IP',
        [Severity::Warning],
    );
});

test('zebra printer basic forced', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: BASIC FORCED
TRAP,
        'ALERT: BASIC FORCED',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 BASIC FORCED',
        [Severity::Warning],
    );
});

test('zebra printer country code error', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: COUNTRY CODE ERROR
TRAP,
        'ALERT: COUNTRY CODE ERROR',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 COUNTRY CODE ERROR',
        [Severity::Warning],
    );
});

test('zebra printer basic runtime', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: BASIC RUNTIME
TRAP,
        'ALERT: BASIC RUNTIME',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 BASIC RUNTIME',
        [Severity::Info],
    );
});

test('zebra printer sgd set', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: SGD SET
TRAP,
        'ALERT: SGD SET',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 SGD SET',
        [Severity::Info],
    );
});

test('zebra printer shutting down', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: SHUTTING DOWN
TRAP,
        'ALERT: SHUTTING DOWN',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 SHUTTING DOWN',
        [Severity::Info],
    );
});

test('zebra printer restarting', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: RESTARTING
TRAP,
        'ALERT: RESTARTING',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 RESTARTING',
        [Severity::Info],
    );
});

test('zebra printer pmcu download', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: PMCU DOWNLOAD
TRAP,
        'ALERT: PMCU DOWNLOAD',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 PMCU DOWNLOAD',
        [Severity::Info],
    );
});

test('zebra printer country code', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: COUNTRY CODE
TRAP,
        'ALERT: COUNTRY CODE',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 COUNTRY CODE',
        [Severity::Info],
    );
});

test('zebra printer media cartridge', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: MEDIA CARTRIDGE
TRAP,
        'ALERT: MEDIA CARTRIDGE',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 MEDIA CARTRIDGE',
        [Severity::Info],
    );
});

test('zebra printer cleaning mode', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: CLEANING MODE
TRAP,
        'ALERT: CLEANING MODE',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 CLEANING MODE',
        [Severity::Info],
    );
});

test('zebra printer label ready', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: LABEL READY
TRAP,
        'ALERT: LABEL READY',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 LABEL READY',
        [Severity::Ok],
    );
});

test('zebra printer ribbon in', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: RIBBON IN
TRAP,
        'ALERT: RIBBON IN',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 RIBBON IN',
        [Severity::Ok],
    );
});

test('zebra printer power on', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: POWER ON
TRAP,
        'ALERT: POWER ON',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 POWER ON',
        [Severity::Ok],
    );
});

test('zebra printer cold start', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 ALERT: COLD START
TRAP,
        'ALERT: COLD START',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 COLD START',
        [Severity::Ok],
    );
});

test('zebra printer druckerpause', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 MELDUNG: DRUCKERPAUSE
TRAP,
        'MELDUNG: DRUCKERPAUSE',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 DRUCKERPAUSE',
        [Severity::Info],
    );
});

test('zebra printer deckel offen', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 MELDUNG: Deckel Offen
TRAP,
        'MELDUNG: Deckel Offen',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 Deckel Offen',
        [Severity::Warning],
    );
});

test('zebra printer eingeschaltet', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 MELDUNG: Eingeschaltet
TRAP,
        'MELDUNG: Eingeschaltet',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 Eingeschaltet',
        [Severity::Ok],
    );
});

test('zebra printer kaltstart', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:23.13
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.1
ESI-MIB::psOutput.7 MELDUNG: KALTSTART
TRAP,
        'MELDUNG: KALTSTART',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.1 KALTSTART',
        [Severity::Ok],
    );
});

test('zebra printer alert cleared', function () {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 19:3:47:37.14
SNMPv2-MIB::snmpTrapOID.0 ZEBRA-QL-MIB::zebra.1.0.2
ESI-MIB::psOutput.7 ERROR CLEARED: HEAD OPEN
TRAP,
        'ERROR CLEARED: HEAD OPEN',
        'Failed to handle ZEBRA-QL-MIB::zebra.1.0.2 alert cleared',
        [Severity::Ok],
    );
});
