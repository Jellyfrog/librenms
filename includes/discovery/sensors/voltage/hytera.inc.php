<?php

$oids = SnmpQuery::numeric()->mibs(['HYTERA-REPEATER-MIB'])->walk('rptVoltage')->values();
d_echo($oids);
if ($oids !== false) {
    echo 'HYTERA-REPEATER-MIB ';

    $divisor = 1;
    $type = 'hytera';

    foreach ($oids as $oid => $descr) {
        $split_oid = explode('.', $oid);
        $index = $split_oid[count($split_oid) - 1];
        $descr = 'Voltage ' . $index;
        $oid = '.1.3.6.1.4.1.40297.1.2.1.2.1.' . $index;
        $voltage = hytera_h2f(str_replace('"', '', SnmpQuery::get($oid)->value()), 2);
        discover_sensor(null, 'voltage', $device, $oid, $index, $type, $descr, $divisor, '1', 11.00, 11.5, 14.5, 15, $voltage);
    }
}
