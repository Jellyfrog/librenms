<?php

$oids = SnmpQuery::numeric()->walk('.1.3.6.1.4.1.18928.1.2.2.1.8.1.2')->values();

if ($oids) {
    echo 'Areca ';

    $divisor = 1000;
    $type = 'areca';
    foreach ($oids as $oid => $descr) {
        $split_oid = explode('.', $oid);
        $index = $split_oid[count($split_oid) - 1];
        $value_oid = '.1.3.6.1.4.1.18928.1.2.2.1.8.1.3.' . $index;
        $current = (SnmpQuery::get($value_oid)->value() / $divisor);
        if (trim($descr, '"') != 'Battery Status') {
            // Battery Status is charge percentage, or 255 when no BBU
            discover_sensor(null, 'voltage', $device, $value_oid, $index, $type, trim($descr, '"'), $divisor, '1', null, null, null, null, $current);
        }
    }
}
