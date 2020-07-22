<?php

$query = [
    ['sda410C', '5'],
    ['sta410C', '6'],
    ['saa410C', '7'],
    ['sdi410C', '8'],
    ['sti410C', '9'],
    ['sai410C', '10'],
    ['ttd440',  '14'],
    ['ttx410C', '15'],
    ['tdx410C', '16'],
    ['sdi480',  '17'],
    ['sti440',  '18'],
];

foreach ($query as $row) {
    if (strpos($device['sysDescr'], $row[0]) !== false) {
        $oid_terra = '.1.3.6.1.4.1.30631.1.';
        $oid = [$oid_terra.$row[1].'.4.1.0', $oid_terra.$row[1].'.4.2.0'];

        $data = snmp_get_multi_oid($device, $oid);
        $hardware = $row[0];
        $version = trim($data[$oid[0]], '"');
        $serial = trim($data[$oid[1]], '"');

        unset($oid);
        unset($data);
    }
}
unset($query);
