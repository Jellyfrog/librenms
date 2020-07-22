<?php

echo ' Siklu Wireless ';

// Poll interface statistics
$mib_oids = [
    'rfInPkts'          => [
        '1',
        'rfInPkts',
        'In Packets',
        'DERIVE',
        ['min' => 0, 'max' => 12500000000],
    ],
    'rfOutPkts'         => [
        '1',
        'rfOutPkts',
        'Out Packets',
        'DERIVE',
        ['min' => 0, 'max' => 12500000000],
    ],
    'rfInGoodPkts'      => [
        '1',
        'rfInGoodPkts',
        'Good Packets',
        'DERIVE',
    ],
    'rfInErroredPkts'   => [
        '1',
        'rfInErroredPkts',
        'Errored Packets',
        'DERIVE',
    ],
    'rfInLostPkts'      => [
        '1',
        'rfInLostPkts',
        'Lost Packets',
        'DERIVE',
    ],
    'rfInOctets'        => [
        '1',
        'rfInOctets',
        'In Packets',
        'DERIVE',
        ['min' => 0, 'max' => 12500000000],
    ],
    'rfOutOctets'       => [
        '1',
        'rfOutOctets',
        'Out Packets',
        'DERIVE',
        ['min' => 0, 'max' => 12500000000],
    ],
    'rfInGoodOctets'    => [
        '1',
        'rfInGoodOctets',
        'Good Packets',
        'DERIVE',
    ],
    'rfInErroredOctets' => [
        '1',
        'rfInErroredOctets',
        'Errored Packets',
        'DERIVE',
    ],
    'rfInIdleOctets'    => [
        '1',
        'rfInIdleOctets',
        'Lost Packets',
        'DERIVE',
    ],
    'rfOutIdleOctets'   => [
        '1',
        'rfOutIdleOctets',
        'Lost Packets',
        'DERIVE',
    ],
];

$mib_graphs = [
    'siklu_rfinterfacePkts',
    'siklu_rfinterfaceOtherPkts',
    'siklu_rfinterfaceOctets',
    'siklu_rfinterfaceOtherOctets',
];

unset($graph, $oids, $oid);

poll_mib_def($device, 'RADIO-BRIDGE-MIB:siklu-interface', 'siklu', $mib_oids, $mib_graphs, $graphs);
