<?php

// Battery Bus Voltage

// upsHighPrecBatteryActualVoltage
$response = SnmpQuery::numeric()->get('.1.3.6.1.4.1.318.1.1.1.2.3.4.0');
$values = $response->values();
$divisor = 10;
$index = '2.3.4.0';
d_echo($response->raw . "\n");

if (! $values) {
    // upsAdvBatteryActualVoltage, used in case high precision is not available
    $response = SnmpQuery::numeric()->get('.1.3.6.1.4.1.318.1.1.1.2.2.8.0');
    $values = $response->values();
    d_echo($response->raw . "\n");
    $divisor = 1;
    $index = '2.2.8.0';
}

if ($values) {
    echo ' Battery Bus ';
    $oid = array_key_first($values);
    $current = $values[$oid];
    $type = 'apc';
    $descr = 'Battery Bus';
    discover_sensor(null, 'voltage', $device, $oid, $index, $type, $descr, $divisor, '1', null, null, null, null, $current / $divisor);
}
unset($values);

//Three Phase Detection & Support

$phasecount = $pre_cache['apcups_phase_count'];
d_echo($phasecount);
d_echo($pre_cache['apcups_phase_count']);
// Check for three phase UPS devices - else skip to normal discovery
if ($phasecount > 2) {
    $oids = SnmpQuery::hideMib()->walk('PowerNet-MIB::upsPhaseOutputVoltage')->valuesByIndex();
    $in_oids = SnmpQuery::hideMib()->walk('PowerNet-MIB::upsPhaseInputVoltage')->valuesByIndex();
    foreach ($oids as $index => $data) {
        $type = 'apcUPS';
        $descr = 'Phase ' . substr((string) $index, -1) . ' Output';
        $voltage_oid = '.1.3.6.1.4.1.318.1.1.1.9.3.3.1.3.' . $index;
        $divisor = 1;
        $voltage = $data['upsPhaseOutputVoltage'] / $divisor;
        if ($voltage >= 0) {
            discover_sensor(null, 'voltage', $device, $voltage_oid, $index, $type, $descr, $divisor, 1, null, null, null, null, $voltage);
        }
    }
    unset($index);
    unset($data);
    foreach ($in_oids as $index => $data) {
        $type = 'apcUPS';
        $voltage_oid = '.1.3.6.1.4.1.318.1.1.1.9.2.3.1.3.' . $index;
        $divisor = 1;
        $voltage = $data['upsPhaseInputVoltage'] / $divisor;
        $in_index = '3.1.3.' . $index;
        if (substr((string) $index, 0, 1) == 2 && $data['upsPhaseInputVoltage'] != -1) {
            $descr = 'Phase ' . substr((string) $index, -1) . ' Bypass Input';
            discover_sensor(null, 'voltage', $device, $voltage_oid, $in_index, $type, $descr, $divisor, 0, null, null, null, null, $voltage);
        } elseif (substr((string) $index, 0, 1) == 1) {
            $descr = 'Phase ' . substr((string) $index, -1) . ' Input';
            discover_sensor(null, 'voltage', $device, $voltage_oid, $in_index, $type, $descr, $divisor, 0, null, null, null, null, $voltage);
        }
    }
} else {
    $in_values = SnmpQuery::numeric()->walk('.1.3.6.1.4.1.318.1.1.8.5.3.3.1.3')->values();
    d_echo($in_values);
    if ($in_values) {
        echo 'APC In ';
        $divisor = 1;
        $type = 'apc';
        foreach ($in_values as $oid => $current) {
            $split_oid = explode('.', $oid);
            $index = $split_oid[count($split_oid) - 3];
            $oid = '.1.3.6.1.4.1.318.1.1.8.5.3.3.1.3.' . $index . '.1.1';
            $descr = 'Input Feed ' . chr(64 + $index);
            discover_sensor(null, 'voltage', $device, $oid, "3.3.1.3.$index", $type, $descr, $divisor, '1', null, null, null, null, $current);
        }
    }
    $out_values = SnmpQuery::numeric()->walk('.1.3.6.1.4.1.318.1.1.8.5.4.3.1.3')->values();
    d_echo($out_values);
    if ($out_values) {
        echo ' APC Out ';
        $divisor = 1;
        $type = 'apc';
        foreach ($out_values as $oid => $current) {
            $split_oid = explode('.', $oid);
            $index = $split_oid[count($split_oid) - 3];
            $oid = '.1.3.6.1.4.1.318.1.1.8.5.4.3.1.3.' . $index . '.1.1';
            $descr = 'Output Feed';
            if (count($out_values) > 1) {
                $descr .= " $index";
            }
            discover_sensor(null, 'voltage', $device, $oid, "4.3.1.3.$index", $type, $descr, $divisor, '1', null, null, null, null, $current);
        }
    }
    // upsHighPrecInputLineVoltage
    $response = SnmpQuery::numeric()->get('.1.3.6.1.4.1.318.1.1.1.3.3.1.0');
    $values = $response->values();
    d_echo($response->raw . "\n");
    $divisor = 10;
    $index = '3.3.1.0';
    if (! $values) {
        // upsAdvInputLineVoltage, used in case high precision is not available
        $response = SnmpQuery::numeric()->get('.1.3.6.1.4.1.318.1.1.1.3.2.1.0');
        $values = $response->values();
        d_echo($response->raw . "\n");
        $divisor = 1;
        $index = '3.2.1.0';
    }
    if ($values) {
        echo ' APC In ';
        $oid = array_key_first($values);
        $current = $values[$oid];
        $type = 'apc';
        $descr = 'Input';
        discover_sensor(null, 'voltage', $device, $oid, $index, $type, $descr, $divisor, '1', null, null, null, null, $current / $divisor);
    }
    // upsHighPrecOutputVoltage
    $response = SnmpQuery::numeric()->get('.1.3.6.1.4.1.318.1.1.1.4.3.1.0');
    $values = $response->values();
    d_echo($response->raw . "\n");
    $divisor = 10;
    $index = '4.3.1.0';
    if (! $values) {
        // upsAdvOutputVoltage, used in case high precision is not available
        $response = SnmpQuery::numeric()->get('.1.3.6.1.4.1.318.1.1.1.4.2.1.0');
        $values = $response->values();
        d_echo($response->raw . "\n");
        $divisor = 1;
        $index = '4.2.1.0';
    }
    if ($values) {
        echo ' APC Out ';
        $oid = array_key_first($values);
        $current = $values[$oid];
        $type = 'apc';
        $descr = 'Output';
        discover_sensor(null, 'voltage', $device, $oid, $index, $type, $descr, $divisor, '1', null, null, null, null, $current / $divisor);
    }
    // rPDUIdentDeviceLinetoLineVoltage
    $response = SnmpQuery::numeric()->get('.1.3.6.1.4.1.318.1.1.12.1.15.0');
    $values = $response->values();
    d_echo($response->raw . "\n");
    if ($values) {
        echo ' Voltage In ';
        $oid = array_key_first($values);
        $current = $values[$oid];
        if ($current >= 0) { // Newer units using rPDU2 can return the following rPDUIdentDeviceLinetoLineVoltage.0; Value (Integer): -1 hence this check.
            $divisor = 1;
            $type = 'apc';
            $index = '1';
            $descr = 'Input';
            discover_sensor(null, 'voltage', $device, $oid, $index, $type, $descr, $divisor, '1', null, null, null, null, $current);
        }
    }
    // rPDU2PhaseStatusVoltage
    $response = SnmpQuery::numeric()->walk('.1.3.6.1.4.1.318.1.1.26.6.3.1.6');
    $values = $response->values();
    d_echo($response->raw . "\n");
    if ($values) {
        echo ' Voltage In ';
        $oid = array_key_first($values);
        $current = $values[$oid];
        if ($current >= 0) { // Some units using rPDU2 can return rPDU2PhaseStatusVoltage.1; Value (Integer): -1 hence this check. Example : AP7900B
            $divisor = 1;
            $type = 'apc';
            $index = '1';
            $descr = 'Input';
            discover_sensor(null, 'voltage', $device, $oid, $index, $type, $descr, $divisor, '1', null, null, null, null, $current);
        }
    }
}
