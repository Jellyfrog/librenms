<?php

$int_desc = snmpwalk_group($device, 'ifMainDesc', 'ARICENT-CFA-MIB');
foreach ($port_stats as $index => $port) {
    if (isset($int_desc[$index]['ifMainDesc'])) {
        $port_stats[$index]['ifAlias'] = $int_desc[$index]['ifMainDesc'];
    }
}
