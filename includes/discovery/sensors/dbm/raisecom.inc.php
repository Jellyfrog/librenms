<?php

echo 'Raisecom';

$multiplier = 1;
$divisor = 1000;
foreach ($pre_cache['raisecomOpticalTransceiverDDMTable'] as $index => $data) {
    foreach ($data as $key => $value) {
        if (isset($value['RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverParameterValue'], $value['RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverDDMValidStatus']) && ($key == 'txPower') && is_numeric($value['RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverParameterValue']) && ($value['RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverDDMValidStatus'] == 1)) {
            $oid = '.1.3.6.1.4.1.8886.1.18.2.2.1.1.2.' . $index . '.3';
            $sensor_type = 'raisecomOpticalTransceiverTxPower';
            $port = PortCache::getByIfIndex(str_replace('1.', '', $index), $device['device_id']);
            $descr = $port?->ifDescr . ' Transmit Power';
            $low_limit = $value['RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverParamLowAlarmThresh'] / $divisor;
            $low_warn_limit = $value['RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverParamLowWarningThresh'] / $divisor;
            $warn_limit = $value['RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverParamHighWarningThresh'] / $divisor;
            $high_limit = $value['RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverParamHighAlarmThresh'] / $divisor;
            $current = $value['RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverParameterValue'] / $divisor;
            $entPhysicalIndex = $index;
            $entPhysicalIndex_measured = 'ports';
            if ($port['ifAdminStatus'] == 'up') {
                discover_sensor(null, 'dbm', $device, $oid, 'tx-' . $index, $sensor_type, $descr, $divisor, $multiplier, $low_limit, $low_warn_limit, $warn_limit, $high_limit, $current, 'snmp', $entPhysicalIndex, $entPhysicalIndex_measured);
            }
        }
        if (isset($value['RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverParameterValue'], $value['RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverDDMValidStatus']) && ($key == 'rxPower') && is_numeric($value['RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverParameterValue']) && ($value['RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverDDMValidStatus'] != 0)) {
            $oid = '.1.3.6.1.4.1.8886.1.18.2.2.1.1.2.' . $index . '.4';
            $sensor_type = 'raisecomOpticalTransceiverRxPower';
            $port = PortCache::getByIfIndex(str_replace('1.', '', $index), $device['device_id']);
            $descr = $port?->ifDescr . ' Receive Power';
            $low_limit = $value['RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverParamLowAlarmThresh'] / $divisor;
            $low_warn_limit = $value['RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverParamLowWarningThresh'] / $divisor;
            $warn_limit = $value['RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverParamHighWarningThresh'] / $divisor;
            $high_limit = $value['RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverParamHighAlarmThresh'] / $divisor;
            $current = $value['RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverParameterValue'] / $divisor;
            $entPhysicalIndex = $index;
            $entPhysicalIndex_measured = 'ports';
            if ($port['ifAdminStatus'] == 'up') {
                discover_sensor(null, 'dbm', $device, $oid, 'rx-' . $index, $sensor_type, $descr, $divisor, $multiplier, $low_limit, $low_warn_limit, $warn_limit, $high_limit, $current, 'snmp', $entPhysicalIndex, $entPhysicalIndex_measured);
            }
        }
    }
}
