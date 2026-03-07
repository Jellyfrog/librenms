<?php

use Illuminate\Support\Facades\Log;
use LibreNMS\Util\Number;

echo 'RFC1628 ';

$battery_volts = SnmpQuery::mibs(['UPS-MIB'])->get('upsBatteryVoltage.0')->value();
if (is_numeric($battery_volts)) {
    $volt_oid = '.1.3.6.1.2.1.33.1.2.5.0';
    $divisor = get_device_divisor($device, $pre_cache['poweralert_serial'] ?? 0, 'voltage', $volt_oid);

    discover_sensor(
        null,
        'voltage',
        $device,
        $volt_oid,
        '1.2.5.0',
        'rfc1628',
        'Battery',
        $divisor,
        1,
        null,
        null,
        null,
        null,
        $battery_volts / $divisor
    );
}

$output_volts = SnmpQuery::hideMib()->walk('UPS-MIB::upsOutputVoltage')->table(1);
foreach ($output_volts as $index => $data) {
    $volt_oid = ".1.3.6.1.2.1.33.1.4.4.1.2.$index";
    $divisor = get_device_divisor($device, $pre_cache['poweralert_serial'] ?? 0, 'voltage', $volt_oid);
    $descr = 'Output';
    if (count($output_volts) > 1) {
        $descr .= " Phase $index";
    }

    $upsOutputVoltage_value = $data['upsOutputVoltage'] ?? null;

    if (is_array($upsOutputVoltage_value)) {
        $upsOutputVoltage_value = reset($upsOutputVoltage_value);
        $volt_oid .= '.0';
    }

    if (! is_numeric($upsOutputVoltage_value)) {
        Log::debug("skipped $descr: $upsOutputVoltage_value is not numeric");

        continue;
    }

    discover_sensor(
        null,
        'voltage',
        $device,
        $volt_oid,
        $index,
        'rfc1628',
        $descr,
        $divisor,
        1,
        null,
        null,
        null,
        null,
        Number::cast($upsOutputVoltage_value) / $divisor
    );
}

$input_volts = SnmpQuery::hideMib()->walk('UPS-MIB::upsInputVoltage')->table(1);
foreach ($input_volts as $index => $data) {
    $volt_oid = ".1.3.6.1.2.1.33.1.3.3.1.3.$index";
    $divisor = get_device_divisor($device, $pre_cache['poweralert_serial'] ?? 0, 'voltage', $volt_oid);
    $descr = 'Input';
    if (count($input_volts) > 1) {
        $descr .= " Phase $index";
    }

    $upsInputVoltage_value = $data['upsInputVoltage'] ?? null;

    if (is_array($upsInputVoltage_value)) {
        $upsInputVoltage_value = reset($upsInputVoltage_value);
        $volt_oid .= '.0';
    }
    if (! is_numeric($upsInputVoltage_value)) {
        Log::debug("skipped $descr: $upsInputVoltage_value is not numeric");

        continue;
    }

    discover_sensor(
        null,
        'voltage',
        $device,
        $volt_oid,
        100 + $index,
        'rfc1628',
        $descr,
        $divisor,
        1,
        null,
        null,
        null,
        null,
        Number::cast($upsInputVoltage_value) / $divisor
    );
}

$bypass_volts = SnmpQuery::hideMib()->walk('UPS-MIB::upsBypassVoltage')->table(1);
foreach ($bypass_volts as $index => $data) {
    $volt_oid = ".1.3.6.1.2.1.33.1.5.3.1.2.$index";
    $divisor = get_device_divisor($device, $pre_cache['poweralert_serial'] ?? 0, 'voltage', $volt_oid);
    $descr = 'Bypass';
    if (count($bypass_volts) > 1) {
        $descr .= " Phase $index";
    }
    $bypassVoltage = $data['upsBypassVoltage'] ?? null;
    if (is_array($bypassVoltage)) {
        $bypassVoltage = reset($bypassVoltage);
        $volt_oid .= '.0';
    }

    if (! is_numeric($bypassVoltage)) {
        Log::debug("skipped $descr: $bypassVoltage is not numeric");

        continue;
    }

    discover_sensor(
        null,
        'voltage',
        $device,
        $volt_oid,
        200 + $index,
        'rfc1628',
        $descr,
        $divisor,
        1,
        null,
        null,
        null,
        null,
        Number::cast($bypassVoltage) / $divisor
    );
}

unset($input_volts, $output_volts, $battery_volts, $bypass_volts);
