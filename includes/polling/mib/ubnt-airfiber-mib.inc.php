<?php

/**
 * @copyright  (C) 2015 Mark Gibbons
 */

// Polling of AirFIBER MIB AP for Ubiquiti AirFIBER Radios
//
// UBNT-AirFIBER-MIB
echo ' UBNT-AirFIBER-MIB ';

// $mib_oids                                (oidindex,dsname,dsdescription,dstype)
$mib_oids = [
    'txFrequency'             => [
        '1',
        'txFrequency',
        'Tx Frequency',
        'GAUGE',
    ],
    'rxFrequency'             => [
        '1',
        'rxFrequency',
        'Rx Frequency',
        'GAUGE',
    ],
    'txPower'                 => [
        '1',
        'txPower',
        'Tx Power',
        'GAUGE',
    ],
    'radioLinkDistM'          => [
        '1',
        'radioLinkDistM',
        'Link Distance',
        'GAUGE',
    ],
    'rxCapacity'              => [
        '1',
        'rxCapacity',
        'Rx Capacity',
        'GAUGE',
    ],
    'txCapacity'              => [
        '1',
        'txCapacity',
        'Tx Capacity',
        'GAUGE',
    ],
    'radio0TempC'             => [
        '1',
        'radio0TempC',
        'Radio 0 Temp',
        'GAUGE',
    ],
    'radio1TempC'             => [
        '1',
        'radio1TempC',
        'Radio 1 Temp',
        'GAUGE',
    ],
    // above here is duplicated in wireless
    'txOctetsOK'              => [
        '1',
        'txOctetsOK',
        'Tx Octets OK',
        'COUNTER',
    ],
    'rxOctetsOK'              => [
        '1',
        'rxOctetsOK',
        'Rx Octets OK',
        'COUNTER',
    ],
    'rxValidUnicastFrames'    => [
        '1',
        'rxValUnicastFrms',
        'TODOa',
        'COUNTER',
    ],
    'rxValidMulticastFrames'  => [
        '1',
        'rxValMulticastFrms',
        'TODOa',
        'COUNTER',
    ],
    'rxValidBroadcastFrames'  => [
        '1',
        'rxValBroadcastFrms',
        'TODO',
        'COUNTER',
    ],
    'txValidUnicastFrames'    => [
        '1',
        'txValUnicastFrms',
        'TODO',
        'COUNTER',
    ],
    'txValidMulticastFrames'  => [
        '1',
        'txValMulticastFrms',
        'TODO',
        'COUNTER',
    ],
    'txValidBroadcastFrames'  => [
        '1',
        'txValBroadcastFrms',
        'TODO',
        'COUNTER',
    ],
    'rxTotalOctets'           => [
        '1',
        'rxTotalOctets',
        'TODO',
        'COUNTER',
    ],
    'rxTotalFrames'           => [
        '1',
        'rxTotalFrms',
        'TODO',
        'COUNTER',
    ],
    'rx64BytePackets'         => [
        '1',
        'rx64BytePkts',
        'TODO',
        'COUNTER',
    ],
    'rx65-127BytePackets'     => [
        '1',
        'rx65-127BytePkts',
        'TODO',
        'COUNTER',
    ],
    'rx128-255BytePackets'    => [
        '1',
        'rx128-255BytePkts',
        'TODO',
        'COUNTER',
    ],
    'rx256-511BytePackets'    => [
        '1',
        'rx256-511BytePkts',
        'TODO',
        'COUNTER',
    ],
    'rx512-1023BytePackets'   => [
        '1',
        'rx512-1023BytePkts',
        'TODO',
        'COUNTER',
    ],
    'rx1024-1518BytesPackets' => [
        '1',
        'rx1024-1518BytePkts',
        'TODO',
        'COUNTER',
    ],
    'rx1519PlusBytePackets'   => [
        '1',
        'rx1519PlusBytePkts',
        'TODO',
        'COUNTER',
    ],
    'txoctetsAll'             => [
        '1',
        'txoctetsAll',
        'TODO',
        'COUNTER',
    ],
    'txpktsAll'               => [
        '1',
        'txpktsAll',
        'TODO',
        'COUNTER',
    ],
    'rxoctetsAll'             => [
        '1',
        'rxoctetsAll',
        'TODO',
        'COUNTER',
    ],
    'rxpktsAll'               => [
        '1',
        'rxpktsAll',
        'TODO',
        'COUNTER',
    ],
];

$mib_graphs = [
    'ubnt_airfiber_RadioFreqs',
    'ubnt_airfiber_TxPower',
    'ubnt_airfiber_LinkDist',
    'ubnt_airfiber_Capacity',
    'ubnt_airfiber_RadioTemp',
    'AF1',
    'AF2',
    'AF3',
    'AF4',
    'AF5',
    'ubnt_airfiber_RFTotOctetsTx',
    'ubnt_airfiber_RFTotPktsTx',
    'ubnt_airfiber_RFTotOctetsRx',
    'ubnt_airfiber_RFTotPktsRx',
];

unset($graph, $oids, $oid);

poll_mib_def($device, 'UBNT-AirFIBER-MIB:UBNT', 'ubiquiti', $mib_oids, $mib_graphs, $graphs);
