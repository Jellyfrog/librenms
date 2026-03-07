<?php

echo 'MGE ';
$oids = SnmpQuery::numeric()->mibs(['MG-SNMP-UPS-MIB'])->walk('mgoutputVoltage')->values();

$numPhase = count($oids);
$i = 0;
foreach ($oids as $oid => $value) {
    $i++;
    $volt_oid = ".1.3.6.1.4.1.705.1.7.2.1.2.$i";
    $descr = 'Output';
    if ($numPhase > 1) {
        $descr .= " Phase $i";
    }

    $current = SnmpQuery::get($volt_oid)->value();
    if (! $current) {
        $volt_oid .= '.0';
        $current = SnmpQuery::get($volt_oid)->value();
    }

    $current /= 10;
    $type = 'mge-ups';
    $divisor = 10;
    $index = $i;

    discover_sensor(null, 'voltage', $device, $volt_oid, $index, $type, $descr, $divisor, '1', null, null, null, null, $current);
}

$oids = SnmpQuery::numeric()->mibs(['MG-SNMP-UPS-MIB'])->walk('mgeinputVoltage')->values();

$numPhase = count($oids);
$i = 0;
foreach ($oids as $oid => $value) {
    $i++;
    $volt_oid = ".1.3.6.1.4.1.705.1.6.2.1.2.$i";
    $descr = 'Input';
    if ($numPhase > 1) {
        $descr .= " Phase $i";
    }

    $current = SnmpQuery::get($volt_oid)->value();
    if (! $current) {
        $volt_oid .= '.0';
        $current = SnmpQuery::get($volt_oid)->value();
    }

    $current /= 10;
    $type = 'mge-ups';
    $divisor = 10;
    $index = (100 + $i);

    discover_sensor(null, 'voltage', $device, $volt_oid, $index, $type, $descr, $divisor, '1', null, null, null, null, $current);
}
